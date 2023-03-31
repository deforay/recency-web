<?php
return array(
    'sss' => array(
        'Application\\Controller\\RolesController' => array(
            'index' => 'allow',
            'add' => 'allow',
        ),
    ),
    'manager' => array(
        'Application\\Controller\\RolesController' => array(
            'index' => 'allow',
            'add' => 'deny',
        ),
    ),
    'admin' => array(
        'Application\\Controller\\RecencyController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-recency' => 'allow',
            'generate-pdf' => 'allow',
        ),
        'Application\\Controller\\VlDataController' => array(
            'index' => 'allow',
            'age-wise-infection-report' => 'allow',
            'email-result' => 'allow',
            'export-long-term-infected-data' => 'allow',
            'export-modality' => 'allow',
            'export-r-infected-data' => 'allow',
            'export-tat-report' => 'allow',
            'export-weekly-report' => 'allow',
            'lt-infection' => 'allow',
            'qc-report' => 'allow',
            'recent-infection' => 'allow',
            'get-sample-data' => 'allow',
            'tat-report' => 'allow',
            'update-vl-sample-result' => 'allow',
            'upload-result' => 'allow',
            'weekly-report' => 'allow',
        ),
        'Application\\Controller\\IndexController' => array(
            'analysis-dashboard' => 'allow',
            'index' => 'allow',
            'quality-control-dashboard' => 'allow',
            'export-recency-data' => 'allow',
        ),
        'Application\\Controller\\CityController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\DistrictController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\FacilitiesController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\GlobalConfigController' => array(
            'index' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\ProvinceController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\UserController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\ManifestsController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\MonitoringController' => array(
            'all-user-login-history' => 'allow',
            'audit-trail' => 'allow',
            'user-activity-log' => 'allow',
        ),
        'Application\\Controller\\PrintResultsController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\QualityCheckController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-qc-data' => 'allow',
        ),
        'Application\\Controller\\SettingsController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'add-sample' => 'allow',
            'edit' => 'allow',
            'edit-sample' => 'allow',
        ),
    ),
    'user' => array(
        'Application\\Controller\\RecencyController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-recency' => 'allow',
            'generate-pdf' => 'allow',
        ),
        'Application\\Controller\\VlDataController' => array(
            'index' => 'allow',
            'age-wise-infection-report' => 'allow',
            'email-result' => 'allow',
            'export-long-term-infected-data' => 'allow',
            'export-modality' => 'allow',
            'export-r-infected-data' => 'allow',
            'export-tat-report' => 'allow',
            'export-weekly-report' => 'allow',
            'lt-infection' => 'allow',
            'qc-report' => 'allow',
            'recent-infection' => 'allow',
            'get-sample-data' => 'allow',
            'tat-report' => 'allow',
            'update-vl-sample-result' => 'allow',
            'upload-result' => 'allow',
            'weekly-report' => 'allow',
        ),
        'Application\\Controller\\IndexController' => array(
            'analysis-dashboard' => 'allow',
            'index' => 'allow',
            'quality-control-dashboard' => 'allow',
            'export-recency-data' => 'allow',
        ),
        'Application\\Controller\\CityController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\DistrictController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\FacilitiesController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\GlobalConfigController' => array(
            'index' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\ProvinceController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\UserController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\ManifestsController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\MonitoringController' => array(
            'all-user-login-history' => 'allow',
            'audit-trail' => 'allow',
            'user-activity-log' => 'allow',
        ),
        'Application\\Controller\\PrintResultsController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\QualityCheckController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-qc-data' => 'allow',
        ),
        'Application\\Controller\\SettingsController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'add-sample' => 'allow',
            'edit' => 'allow',
            'edit-sample' => 'allow',
        ),
    ),
);
