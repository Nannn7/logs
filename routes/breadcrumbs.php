<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

if (!Breadcrumbs::exists('logs')) {
    Breadcrumbs::for('logs', function (BreadcrumbTrail $trail) {
        $trail->push('Log');
    });
}

Breadcrumbs::for('logs.audit', function (BreadcrumbTrail $trail) {
    $trail->parent('logs');
    $trail->push('Log Dokumen', route('logs.audit.index'));
});

Breadcrumbs::for('logs.system', function (BreadcrumbTrail $trail) {
    $trail->parent('logs');
    $trail->push('Log Sistem', route('logs.system.index'));
});
