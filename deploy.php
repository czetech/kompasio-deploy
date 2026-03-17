<?php
namespace Deployer;

require 'recipe/common.php';

set('repository', 'https://github.com/czepiec/nette-example.git');
set('astro_repository', 'https://github.com/czetech/kompasio-site.git');

add('shared_dirs', ['log', 'temp']);

host('kompasio');

desc('Build Astro site');
task('deploy:astro', function () {
    $astroDir = '{{release_path}}/.astro-build';
    run('git clone --depth 1 {{astro_repository}} ' . $astroDir);
    run('cd ' . $astroDir . ' && npm ci');
    run('cd ' . $astroDir . ' && npm run build');
    run('cp -r ' . $astroDir . '/dist-astro/* {{release_path}}/www/');
    run('rm -rf ' . $astroDir);
});

desc('Upload htaccess');
task('deploy:htaccess', function () {
    upload(__DIR__ . '/deploy-htaccess', '{{release_path}}/www/.htaccess');
});

task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:astro',
    'deploy:htaccess',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
