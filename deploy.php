<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:lucasgarbe/steppenreg.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('your-server.com')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/steppenreg');

// Hooks

after('deploy:failed', 'deploy:unlock');
