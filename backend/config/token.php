<?php

return [
    'sanctum_refresh_threshold' => (int) env('TOKEN_REFRESH_THRESHOLD', 5),
    'revoke_old_on_rotate'      => (bool) env('TOKEN_REVOKE_OLD_ON_ROTATE', false),
];
