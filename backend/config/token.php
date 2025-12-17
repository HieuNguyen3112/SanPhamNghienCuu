<?php

return [
    // Backward-compat: prefer SANCTUM_* env; keep TOKEN_* for legacy setups
    'sanctum_refresh_threshold' => (int) env('SANCTUM_REFRESH_THRESHOLD', env('TOKEN_REFRESH_THRESHOLD', 5)),
    'revoke_old_on_rotate'      => (bool) env('SANCTUM_REVOKE_OLD_ON_ROTATE', env('TOKEN_REVOKE_OLD_ON_ROTATE', false)),
];
