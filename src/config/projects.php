<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-Approve Projects
    |--------------------------------------------------------------------------
    |
    | When set to true, new projects will be automatically approved and
    | visible to the public immediately, bypassing the review process.
    | When set to false, new projects will be created as drafts and require
    | explicit submission for admin review.
    |
    */
    'auto_approve' => env('PROJECTS_AUTO_APPROVE', false),
];
