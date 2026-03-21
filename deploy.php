<?php

namespace Deployer;

require 'recipe/common.php';

host('kompasio.sk');

set('repository', 'git@gitlab.com:kompasio-group/kompasio.git');
set('branch', 'production');
set('keep_releases', 3);
set('bin/php', '/usr/bin/php8.1');
set('writable_mode', 'chmod');
set('astro_repository', 'https://github.com/czetech/kompasio-site.git');
set('astro_branch', 'main');

add('shared_files', ['config/local.neon']);
add('shared_dirs', ['public_html/source', 'public_html/thumbs']);
add('writable_dirs', ['log', 'temp']);

desc('Build and copy Astro static site');
task('deploy:astro', function () {
	$astroDir = '{{release_path}}/.astro-build';
	run('git clone --branch {{astro_branch}} --depth 1 {{astro_repository}} ' . $astroDir);
	run('cd ' . $astroDir . ' && npm ci');
	run('cd ' . $astroDir . ' && npm run build');
	run('rm -r ' . $astroDir . '/dist-astro/app');
	run('cp -r ' . $astroDir . '/dist-astro/* {{release_path}}/public_html/');
	run('cp ' . $astroDir . '/dist-cookieconsent/cookieconsent.js {{release_path}}/public_html/frontend/assets/js/cookieconsent.js');
	run('rm -rf ' . $astroDir);
});

desc('Upload .htaccess');
task('deploy:htaccess', function () {
	upload(__DIR__ . '/deploy-htaccess', '{{release_path}}/public_html/.htaccess');

	$auth = get('auth');
	if ($auth) {
		[$user, $password] = explode(':', $auth, 2);
		$hash = password_hash($password, PASSWORD_BCRYPT);
		$htpasswd = $user . ':' . $hash;
		run('echo ' . escapeshellarg($htpasswd) . ' > {{release_path}}/public_html/.htpasswd');

		$htpasswdPath = run('readlink -f {{deploy_path}}') . '/current/public_html/.htpasswd';
		$authLines = implode('\n', [
			'AuthName \"Restricted Access\"',
			'AuthType Basic',
			'AuthUserFile ' . $htpasswdPath,
			'Require valid-user',
		]);
		run('sed -i "s|Require all granted|' . $authLines . '|" {{release_path}}/public_html/.htaccess');
	}
});

desc('Create /app/sk/map symlink for Nette');
task('create-map-link', function () {
	run('mkdir -p {{deploy_path}}/current/public_html/app/sk');
	run('ln -s {{deploy_path}}/current/public_html {{deploy_path}}/current/public_html/app/sk/map');
});

desc('Update Nette ORM schema');
task('orm:schema-tool:update', function () {
	run('php8.1 {{release_path}}/bin/command.php orm:schema-tool:update --force');
});

desc('Generate Nette ORM proxies');
task('orm:generate-proxies', function () {
	run('php8.1 {{release_path}}/bin/command.php orm:generate-proxies');
});

desc('Deploy kompasio.sk');
task('deploy', [
	'deploy:prepare',
	'deploy:vendors',
	'deploy:astro',
	'deploy:htaccess',
	'deploy:publish',
]);

after('deploy:symlink', 'create-map-link');
after('deploy:symlink', 'orm:schema-tool:update');
after('deploy:symlink', 'orm:generate-proxies');

after('deploy:failed', 'deploy:unlock');
