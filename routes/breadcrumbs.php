<?php

    use Diglactic\Breadcrumbs\Breadcrumbs;
    use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

    if (!Breadcrumbs::exists('logs')) {
        Breadcrumbs::for('logs', function (BreadcrumbTrail $trail) {
            $trail->push('Logs');
        });
    }

    Breadcrumbs::for('logs.audit', function (BreadcrumbTrail $trail) {
        $trail->parent('logs');
        $trail->push('Audit Logs', route('logs.audit.index'));
    });
