<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:lucasgarbe/steppenreg.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('bab.steppenwolf-berlin.de')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/steppenreg');

// Hooks

after('deploy:failed', 'deploy:unlock');

// Define pnpm tasks
task('pnpm:install', function () {
    run('cd {{release_path}} && /home/deployer/.local/share/pnpm/pnpm install');
})->desc('Install pnpm dependencies');

task('pnpm:build', function () {
    run('cd {{release_path}} && /home/deployer/.local/share/pnpm/pnpm run build');
})->desc('Build assets with pnpm');

// Add the tasks to the deployment flow
after('deploy:vendors', 'pnpm:install');
after('deploy:vendors', 'pnpm:build');

before('deploy:publish', 'artisan:queue:restart');
before('deploy:publish', 'artisan:pulse:restart');
