<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/composer.php';

set('ssh_multiplexing', false);
set('username', 'jchorvat@cassiopea.sk');

// Config
set('bin/php', '/usr/bin/php8.1');
set('repository', 'git@gitlab.com:kompasio-group/kompasio.git');
set('branch', 'production');
set('astro_repository', 'https://github.com/czetech/kompasio-site.git');

host('kompasio.sk');

add('shared_files', [
	'config/local.neon'
]);
add('shared_dirs', [
	'public_html/source',
	'public_html/thumbs'
]);

set('writable_mode', 'chmod');
add('writable_dirs', [
	'log',
	'temp'
]);

task('orm:schema-tool:update', function () {
	run('php8.1 {{release_path}}/bin/command.php orm:schema-tool:update --force');
});
task('orm:generate-proxies', function () {
	run('php8.1 {{release_path}}/bin/command.php orm:generate-proxies');
});
task('create-map-link', function () {
	run('mkdir {{deploy_path}}/current/public_html/app');
	run('mkdir {{deploy_path}}/current/public_html/app/sk');
	run('ln -s {{deploy_path}}/current/public_html {{deploy_path}}/current/public_html/app/sk/map');
});
task('deploy:astro', function () {
	$astroDir = '{{release_path}}/.astro-build';
	run('git clone --depth 1 {{astro_repository}} ' . $astroDir);
	run('cd ' . $astroDir . ' && npm ci');
	run('cd ' . $astroDir . ' && npm run build');
	run('cp -r ' . $astroDir . '/dist-astro/* {{release_path}}/public_html/');
	run('rm -rf ' . $astroDir);
});
task('deploy:htaccess', function () {
	upload(__DIR__ . '/deploy-htaccess', '{{release_path}}/public_html/.htaccess');
});

after('deploy:symlink', 'create-map-link');
after('deploy:symlink', 'orm:schema-tool:update');
after('deploy:symlink', 'orm:generate-proxies');
after('deploy:symlink', 'deploy:astro');
after('deploy:symlink', 'deploy:htaccess');

// Hooks

after('deploy:failed', 'deploy:unlock');
