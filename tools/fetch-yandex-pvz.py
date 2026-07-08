#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Выгрузка базы ПВЗ Яндекс Маркета / Яндекс Доставки.

Источник — публичный эндпоинт официального виджета Яндекс Доставки
(https://widget-pvz.dostavka.yandex.net/widget.js), работает без токена:

    POST https://widget-pvz.dostavka.yandex.net/api/b2b/platform/pickup-points/list
    body: {"latitude":{"from":..,"to":..},"longitude":{"from":..,"to":..}}

Это тот же метод, что и в официальном B2B API Яндекс Доставки
(/api/b2b/platform/pickup-points/list), но проксированный хостом виджета.
Большие области сервер обрезает, поэтому качаем тайлами с автоделением.

Результат — JSON в формате импорта темы minoksidil
(Minoksidil → Пункты выдачи (ПВЗ) → Импорт, режим «заменить всё» не выбирать,
лучше «добавить», предварительно очистив службу):

    [{"service":"yandex","code":"...","name":"...","address":"...",
      "city":"...","lat":55.7,"lng":37.6,"work_time":"...","phone":"..."}]

Примеры:
    python fetch-yandex-pvz.py                         # Москва и область
    python fetch-yandex-pvz.py --bbox 59.6,60.25,29.5,30.8 --out spb.json
    python fetch-yandex-pvz.py --all                   # не только Маркет
"""

import argparse
import json
import sys
import time
import urllib.request

# Windows-консоль может быть в cp1251 — не падаем на юникоде в логах
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

API = "https://widget-pvz.dostavka.yandex.net/api/b2b/platform/pickup-points/list"
MIN_CELL = 0.02   # минимальный размер тайла в градусах — дальше не делим
PAUSE    = 0.15   # пауза между запросами, сек
SPLIT_AT = 1000   # защитное деление тайла при слишком большом числе точек

DAYS = {1: "Пн", 2: "Вт", 3: "Ср", 4: "Чт", 5: "Пт", 6: "Сб", 7: "Вс"}


def fetch_cell(lat_from, lat_to, lng_from, lng_to):
    """Один запрос к API. Возвращает list точек или None при ошибке/обрезке."""
    body = json.dumps({
        "latitude":  {"from": lat_from, "to": lat_to},
        "longitude": {"from": lng_from, "to": lng_to},
    }).encode()
    req = urllib.request.Request(API, data=body, headers={
        "Content-Type": "application/json",
        "User-Agent": "Mozilla/5.0 (pvz-fetch script)",
    })
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            raw = r.read()
        return json.loads(raw).get("points", [])
    except Exception:
        return None  # обрезанный JSON / сеть / HTTP-ошибка → тайл делим


def walk(lat_from, lat_to, lng_from, lng_to, store, depth=0):
    """Рекурсивный обход: не получилось целиком — делим тайл на 4 части."""
    pts = fetch_cell(lat_from, lat_to, lng_from, lng_to)
    time.sleep(PAUSE)

    can_split = (lat_to - lat_from) > MIN_CELL or (lng_to - lng_from) > MIN_CELL
    # Обрезанный ответ или подозрительно много точек — делим тайл
    if (pts is None or len(pts) >= SPLIT_AT) and can_split:
        lat_mid = (lat_from + lat_to) / 2
        lng_mid = (lng_from + lng_to) / 2
        walk(lat_from, lat_mid, lng_from, lng_mid, store, depth + 1)
        walk(lat_from, lat_mid, lng_mid, lng_to,  store, depth + 1)
        walk(lat_mid,  lat_to,  lng_from, lng_mid, store, depth + 1)
        walk(lat_mid,  lat_to,  lng_mid,  lng_to,  store, depth + 1)
        return

    if pts is None:
        print(f"  ! пропуск тайла {lat_from:.3f},{lng_from:.3f} (ошибка API)")
        return

    for p in pts:
        pid = p.get("id")
        if pid and pid not in store:
            store[pid] = p
    if pts:
        print(f"  тайл {lat_from:.2f}..{lat_to:.2f} / {lng_from:.2f}..{lng_to:.2f}"
              f" → {len(pts)} точек (всего {len(store)})", flush=True)


def format_work_time(schedule):
    """restrictions → 'Пн–Пт 10:00–22:00; Сб–Вс 10:00–20:00'."""
    if not schedule or not schedule.get("restrictions"):
        return ""
    intervals = {}  # day -> "10:00–22:00"
    for r in schedule["restrictions"]:
        tf, tt = r.get("time_from", {}), r.get("time_to", {})
        val = "{:02d}:{:02d}–{:02d}:{:02d}".format(
            tf.get("hours", 0), tf.get("minutes", 0),
            tt.get("hours", 0), tt.get("minutes", 0))
        for d in r.get("days", []):
            intervals[d] = val

    parts, run = [], []
    for d in range(1, 8):
        if d in intervals and run and intervals[d] == intervals[run[-1]] and d == run[-1] + 1:
            run.append(d)
        else:
            if run:
                a, b = run[0], run[-1]
                label = DAYS[a] if a == b else f"{DAYS[a]}–{DAYS[b]}"
                parts.append(f"{label} {intervals[a]}")
            run = [d] if d in intervals else []
    if run:
        a, b = run[0], run[-1]
        label = DAYS[a] if a == b else f"{DAYS[a]}–{DAYS[b]}"
        parts.append(f"{label} {intervals[a]}")
    return "; ".join(parts)


def to_import_row(p):
    addr = p.get("address") or {}
    full = addr.get("full_address") or ", ".join(filter(None, [
        addr.get("locality"), addr.get("street"), addr.get("house")]))
    pos = p.get("position") or {}
    return {
        "service":   "yandex",
        "code":      p.get("id", ""),
        "name":      p.get("name", "Пункт выдачи Яндекс"),
        "address":   full,
        "city":      addr.get("locality", ""),
        "lat":       pos.get("latitude"),
        "lng":       pos.get("longitude"),
        "work_time": format_work_time(p.get("schedule")),
        "phone":     (p.get("contact") or {}).get("phone", ""),
    }


def main():
    ap = argparse.ArgumentParser(description="Выгрузка ПВЗ Яндекса в JSON для импорта")
    ap.add_argument("--bbox", default="55.30,56.10,36.80,38.30",
                    help="lat_from,lat_to,lng_from,lng_to (по умолчанию Москва и область)")
    ap.add_argument("--cell", type=float, default=0.10, help="стартовый размер тайла, градусы")
    ap.add_argument("--out", default="yandex-pvz.json", help="файл результата")
    ap.add_argument("--all", action="store_true",
                    help="сохранить все точки сети (вкл. партнёрские и почту), не только Яндекс Маркет")
    ap.add_argument("--chunk", type=int, default=0,
                    help="разбить результат на файлы по N точек (0 = один файл)")
    args = ap.parse_args()

    lat_from, lat_to, lng_from, lng_to = map(float, args.bbox.split(","))
    store = {}

    lat = lat_from
    while lat < lat_to:
        lng = lng_from
        while lng < lng_to:
            walk(lat, min(lat + args.cell, lat_to), lng, min(lng + args.cell, lng_to), store)
            lng += args.cell
        lat += args.cell

    points = list(store.values())
    if not args.all:
        points = [p for p in points
                  if p.get("is_yandex_branded")
                  or str(p.get("operator_id", "")).startswith("market")]

    rows = [to_import_row(p) for p in points]
    rows = [r for r in rows if r["lat"] and r["lng"]]

    if args.chunk > 0 and len(rows) > args.chunk:
        base = args.out.rsplit(".", 1)[0]
        n_parts = 0
        for i in range(0, len(rows), args.chunk):
            n_parts += 1
            part_name = f"{base}-{n_parts}.json"
            with open(part_name, "w", encoding="utf-8") as f:
                json.dump(rows[i:i + args.chunk], f, ensure_ascii=False, indent=1)
            print(f"  часть {n_parts}: {part_name} ({len(rows[i:i + args.chunk])} точек)")
    else:
        with open(args.out, "w", encoding="utf-8") as f:
            json.dump(rows, f, ensure_ascii=False, indent=1)

    with_addr = sum(1 for r in rows if r["address"])
    print(f"\nГотово: {len(rows)} точек ({with_addr} с адресом) → {args.out}")
    print("Импорт: Minoksidil → Пункты выдачи (ПВЗ) → вставить содержимое файла или загрузить файл.")


if __name__ == "__main__":
    main()
