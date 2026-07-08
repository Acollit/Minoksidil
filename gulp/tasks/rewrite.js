import revRewrite from "gulp-rev-rewrite";
import { readFileSync } from "fs";

export const rewrite = (done) => {
  const manifest = readFileSync(`${app.paths.base.build}/rev.json`);
  let pending = 2;
  const check = () => --pending === 0 && done();

  app.gulp.src(`${app.paths.buildCssFolder}/*.css`)
    .pipe(revRewrite({ manifest }))
    .pipe(app.gulp.dest(app.paths.buildCssFolder))
    .on('end', check)
    .on('error', done);

  app.gulp.src(`${app.paths.base.build}/**/*.html`)
    .pipe(revRewrite({ manifest }))
    .pipe(app.gulp.dest(app.paths.base.build))
    .on('end', check)
    .on('error', done);
}

