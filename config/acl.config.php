<?php
return array(
    'sss' => array(
        'Application\\Controller\\Roles' => array(
            'index' => 'allow',
            'add' => 'allow',
        ),
    ),
    'manager' => array(
        'Application\\Controller\\Roles' => array(
            'index' => 'allow',
            'add' => 'deny',
        ),
    ),
    'admin' => array(
        'Application\\Controller\\Recency' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-recency' => 'allow',
            'generate-pdf' => 'allow',
        ),
        'Application\\Controller\\VlData' => array(
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
        'Application\\Controller\\Index' => array(
            'analysis-dashboard' => 'allow',
            'index' => 'allow',
            'quality-control-dashboard' => 'allow',
            'export-recency-data' => 'allow',
        ),
        'Application\\Controller\\City' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\District' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Facilities' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\GlobalConfig' => array(
            'index' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Province' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\User' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Manifests' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Monitoring' => array(
            'all-user-login-history' => 'allow',
            'audit-trail' => 'allow',
            'user-activity-log' => 'allow',
        ),
        'Application\\Controller\\PrintResults' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\QualityCheck' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-qc-data' => 'allow',
        ),
        'Application\\Controller\\Settings' => array(
            'index' => 'allow',
            'add' => 'allow',
            'add-sample' => 'allow',
            'edit' => 'allow',
            'edit-sample' => 'allow',
        ),
    ),
    'user' => array(
        'Application\\Controller\\Recency' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-recency' => 'allow',
            'generate-pdf' => 'allow',
        ),
        'Application\\Controller\\VlData' => array(
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
        'Application\\Controller\\Index' => array(
            'analysis-dashboard' => 'allow',
            'index' => 'allow',
            'quality-control-dashboard' => 'allow',
            'export-recency-data' => 'allow',
        ),
        'Application\\Controller\\City' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\District' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Facilities' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\GlobalConfig' => array(
            'index' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Province' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\User' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Manifests' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Monitoring' => array(
            'all-user-login-history' => 'allow',
            'audit-trail' => 'allow',
            'user-activity-log' => 'allow',
        ),
        'Application\\Controller\\PrintResults' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\QualityCheck' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-qc-data' => 'allow',
        ),
        'Application\\Controller\\Settings' => array(
            'index' => 'allow',
            'add' => 'allow',
            'add-sample' => 'allow',
            'edit' => 'allow',
            'edit-sample' => 'allow',
        ),
    ),
);
