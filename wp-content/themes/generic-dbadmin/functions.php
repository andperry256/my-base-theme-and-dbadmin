<?php
//==============================================================================

require ("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
add_action( 'init', 'run_session', 1);
if (!defined('WP_POST_REVISIONS')) {
    define( 'WP_POST_REVISIONS', 2 );
}

//==============================================================================

function is_local()
{
    global $location;
    return ($location == 'local');
}

//==============================================================================
/*
Custom visibility rules for the 'If Menu' plugin
*/
//==============================================================================

function menu_condition_is_local($conditions)
{
    $conditions[] = [
        'id'        =>  'is_local',
        'name'      =>  __('Is Local', 'i18n-domain'),
        'condition' =>  function ($item) {
                            return is_local();
                        },
    ];
    return $conditions;
}
add_filter('if_menu_conditions', 'menu_condition_is_local');

//==============================================================================
