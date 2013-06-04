<?php 
    
    $arrHttpStatusColor = array(
        '200' => '#176A17',
        '404' => '#FF0000'
    );
?>

<script>window.jQuery || document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"><\/script>')</script>
<style type="text/css">
    #hathoora_debug { font-size:12px; font-family:arial; position: fixed; bottom: 0%; right: 0px; background: #D5D6D2;  width:100%; height: 30px; border-top:1px solid #BCBCBC; z-index:1000; vertical-align:middle; line-height:16px; }
    #hathoora_debug > div { border-right:1px solid #BCBCBC; float:left; padding-left:9px; padding-right:8px; height:30px; padding-top:7px; }
    #hathoora_debug div.hathoora_logo { padding-left:5px; }
    #hathoora_debug div.hathoora_logo img { vertical-align:top !important; }
    #hathoora_debug div.hathoora_summary { float:right; border-right:none; border-left:1px solid #BCBCBC; padding-top:0;  }
    
    /* common properties for toggle sections */
    #hathoora_debug div.hathoora_opened { background:#CCDAB0; border-top:1px solid #CCDAB0; position:relative; top:-1px; }
    #hathoora_debug div.hathoora_section_toggle { cursor:pointer; }
    #hathoora_debug div.hathoora_section_table_wrapper { display:none;  background:#CCDAB0; height:280px;  padding:15px 0 15px 0; left:0px; bottom:31px; position:fixed; width:100%; border-top:1px solid #BCBCBC;  }
    #hathoora_debug div.hathoora_section_table_wrapper .hathoora_section_table { max-height:240px; overflow:auto; clear:both; }
    #hathoora_debug div.hathoora_section_table_wrapper table { width:98%; border-collapse:collapse; font-size:12px; border:1px solid #BCBCBC; }
    #hathoora_debug div.hathoora_section_table_wrapper table thead th { background:#D5D6D2; text-align:left; padding-left:10px; border-right:1px solid #BCBCBC; border-bottom:1px solid #BCBCBC;  }    
    #hathoora_debug div.hathoora_section_table_wrapper table tbody td { padding-left: 10px; border-right:1px solid #BCBCBC; border-bottom:1px solid #BCBCBC;   }
    #hathoora_debug div.hathoora_section_table_wrapper table tbody td.n { padding:0 10px 0 0; text-align:right; }
    #hathoora_debug div.hathoora_section_table_wrapper table tr.odd { background:#F4F4F4; }
    #hathoora_debug div.hathoora_section_table_wrapper table tbody tr.even { background:#EBEBEB; }
    /* common properties for tabs */
    #hathoora_debug div.hathoora_section_tabs { padding-left:15px; }
    #hathoora_debug div.hathoora_section_tabs .hathoora_section_tab { float:left; padding:5px 8px; background:#609440; margin-right:2px; color:#fff; border:1px solid #617566; border-bottom:none; cursor:pointer; }
    #hathoora_debug div.hathoora_section_tabs .hathoora_section_tab_selected {  color:#609440; background:#fff; border:1px solid #BCBCBC; border-bottom:none; } 
    #hathoora_debug div.hathoora_section_tabs .hathoora_section_tab_content { display:block;  max-height:240px; overflow:auto; clear:both; background:#fff; border:1px solid #BCBCBC; width:97%; padding:10px; border-top:none; }
    
    
    /* config */
    #hathoora_debug div.hathoora_config .hathoora_section_table { max-height:280px; overflow:auto; }
    #hathoora_debug div.hathoora_config table thead th.hathoora_config_key { width:20%; }
    #hathoora_debug div.hathoora_config table thead th.hathoora_config_value { width:80%; }
    
    /*  log */
    #hathoora_debug div.hathoora_log table thead th.hathoora_log_num { width:2%; }
    #hathoora_debug div.hathoora_log table thead th.hathoora_log_time { width:7%; }
    #hathoora_debug div.hathoora_log table thead th.hathoora_log_level { width:8%; }
    #hathoora_debug div.hathoora_log table thead th.hathoora_log_memory { width:7%; }
    #hathoora_debug div.hathoora_log table thead th.hathoora_log_message { width:77%; }
    
    /* profiling */
    #hathoora_debug div.hathoora_profiling table thead th.hathoora_profile_num { width:2%; }
    #hathoora_debug div.hathoora_profiling table thead th.hathoora_profile_time { width:3%; }
    #hathoora_debug div.hathoora_profiling table thead th.hathoora_profile_name { width:7%; }
    #hathoora_debug div.hathoora_profiling table thead th.hathoora_profile_message { padding-right:10px; }
    #hathoora_debug div.hathoora_profiling table thead th.hathoora_profile_took { width:7%; }
    #hathoora_debug div.hathoora_profiling table tbody td span { color:#BCBCBC; font-size:11px; }
    #hathoora_debug div.hathoora_profiling  .hathoora_profile_error { padding:2px 4px; border:1px solid #FF0000; color:#990000; background:#FFE8E8;  }

</style>
<script type="text/javascript">
    // hide all tab contens
    function hathooraHideAllTabs()
    {
        $('#hathoora_debug .hathoora_section_tab_content').hide();
        $('#hathoora_debug .hathoora_section_tab').removeClass('hathoora_section_tab_selected');
    }
    
    $(document).ready(function () 
    {
        hathooraHideAllTabs();
        
        // section opener
        $('#hathoora_debug div.hathoora_section_toggle').click(function()
        {
            section = $(this).parent().attr('section');
            if (!$('#hathoora_debug div.hathoora_' + section + ' div.hathoora_section_table_wrapper').is(":visible"))
            {
                // hide all first
                $('#hathoora_debug div.hathoora_section_table_wrapper').hide('fast').parents('div[section]').removeClass('hathoora_opened');
                // then show this one
                $('#hathoora_debug div.hathoora_' + section + ' div.hathoora_section_table_wrapper').show('fast').parents('div.hathoora_' + section).addClass('hathoora_opened');
                
                // need to show first tab content?
                if ($('#hathoora_debug div.hathoora_' + section + ' div.hathoora_section_tabs .hathoora_section_tab').length)
                {
                    $($('#hathoora_debug div.hathoora_' + section + ' div.hathoora_section_tabs .hathoora_section_tab')[0]).trigger('click');
                }
            }
            else
            {
                $('#hathoora_debug div.hathoora_' + section + ' div.hathoora_section_table_wrapper').hide('fast').parents('div.hathoora_' + section).removeClass('hathoora_opened');
            }
        });
        
        // tabs
        $('#hathoora_debug div.hathoora_section_tabs .hathoora_section_tab').click(function()
        {
            section = $(this).parents('div[section]').attr('section');
            tab = $(this).attr('tab');
            hathooraHideAllTabs();
            $(this).addClass('hathoora_section_tab_selected');
            $('#hathoora_debug div.hathoora_' + section + ' .hathoora_section_tab_content[tab="' + tab +'"]').show();
        });
    });
</script>


<div id="hathoora_debug">
    <div class="hathoora_summary">
        <b>Time:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo number_format(round(\hathoora\logger\profiler::microtimeDiff(HATHOORA_PROFILE_START_TIME, $scriptEndTime) * 1000, 3), 3); ?> msec<br/>
        <b>Memory:</b> &nbsp;&nbsp;<?php echo number_format(round($totalMemory/1024, 2), 2); ?> KB 
    </div>
    
    <div class="hathoora_logo">
        <img src="http://cdn1.iconfinder.com/data/icons/strabo/24/hammer.png" title="Hathoora <?php echo $version;?>" align="absmiddle"/>
        <?php echo HATHOORA_ENV; ?>
    </div>
    
    <div class="hathoora_route" section="route">
        <div class="hathoora_section_toggle"><?php echo HATHOORA_APP . '<span style="color:#975301;"> / </span>' . $controller->getControllerName() . '<span style="color:#975301;"> / </span>' . $controller->getControllerActionName() . ' (<span style="color:' . $arrHttpStatusColor[$httpStatus] .';">'. $httpStatus .'</span>)'?></div>
        <div class="hathoora_section_table_wrapper hathoora_section_tabs">
            <div class="hathoora_section_tab" tab="request">Request</div>
            <div class="hathoora_section_tab" tab="response">Response</div>
            <div class="hathoora_section_tab_content" tab="request">
                <b>Request UUID:</b> <?php echo HATHOORA_UUID; ?> <br/>
                <pre>
                    <?php print_r($request); ?>
                </pre>
            </div>
            <div class="hathoora_section_tab_content" tab="response">
                <pre>
                    <?php /* print_r($response); */ ?>
                </pre>
            </div>
        </div>
    </div>
    
    <div class="hathoora_config" section="config">
        <div class="hathoora_section_toggle">Configutation</div>
        <div class="hathoora_section_table_wrapper">
            <center class="hathoora_section_table">
                <table>
                    <thead>
                        <tr>
                            <th class="hathoora_config_key">Key</th>
                            <th class="hathoora_config_value">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (is_array($arrConfigs) && count($arrConfigs))
                            {
                                $i = -1;
                                foreach($arrConfigs as $c => $_arr)
                                {
                                    foreach($_arr as $k => $v)
                                    {
                                        $i++;
                                        $class = 'even';
                                        if ($i % 2 == 0)
                                            $class = 'odd';
                                            
                                        echo '
                                        <tr class="'. $class .'">
                                            <td>'. $c . '.' . $k .'</td>
                                            <td>'. (is_array($v) || is_object($v) ? json_encode($v) : $v) .'</td>
                                        </tr>';
                                    }
                                }
                            }
                            else
                            {
                                echo '
                                <tr>
                                    <td colspan="2">No configuration found.</td>
                                </tr>';                    
                            }
                        ?>
                    </tbody>
                </table>
            </center>
        </div>
    </div>

    <div class="hathoora_log" section="log">
        <div class="hathoora_section_toggle">Logging (<?php echo count($arrLog); ?>)</div>
        <div class="hathoora_section_table_wrapper">
            <div style="padding-left:10px; padding-bottom:5px;">
                <b>logger.logging.enabled:</b> <?php echo $loggingStatus; ?><br/>
                <b>logger.webprofiler.content_type:</b> <?php echo $contentTypeRegex; ?> <br/>
            </div>
            <center class="hathoora_section_table">
                <table>
                    <thead>
                        <tr>
                            <th class="hathoora_log_num">#</th>
                            <th class="hathoora_log_time">Time&nbsp;(msec)</th>
                            <th class="hathoora_log_level">Level</th>
                            <th class="hathoora_log_memory">Memory&nbsp;(KB)</th>
                            <th class="hathoora_log_message">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (is_array($arrLog) && count($arrLog))
                            {
                                $k = 0;
                                foreach($arrLog as $i => $_arrLog)
                                {
                                    $k++;
                                    $class = 'even';
                                    if ($i % 2 == 0)
                                        $class = 'odd';
                                        
                                    $time = $_arrLog['time'];
                                    $memory = number_format(round($_arrLog['memory']/1024, 2), 2);
                                    
                                    echo '
                                    <tr class="'. $class .'">
                                        <td>'. $k .'</td>
                                        <td class="n">'. number_format(round(\hathoora\logger\profiler::microtimeDiff(HATHOORA_PROFILE_START_TIME, $time) * 1000, 3), 3) .'</td>
                                        <td>'. $_arrLog['level'] .'</td>
                                        <td class="n">'. $memory .'</td>
                                        <td>'. $_arrLog['message'] .'</td>
                                    </tr>';
                                }
                            }
                            else
                            {
                                echo '
                                <tr>
                                    <td colspan="4">Nothing is being logged, make sure you have defined "logger.logging.enabled" and "logger.logging.level" in configuration files properly.</td>
                                </tr>';                    
                            }
                        ?>
                    </tbody>
                </table>
            </center>
        </div>
    </div>

    <div class="hathoora_profiling" section="profiling">
        <div class="hathoora_section_toggle">Profiling</div>
        <div class="hathoora_section_table_wrapper hathoora_section_tabs">
            <?php
                if (is_array($arrProfile))
                {
                    //printr($arrProfile);
                    $arrProfileKeys = array_keys($arrProfile);
                    foreach ($arrProfileKeys as $k)
                    {
                        echo '<div class="hathoora_section_tab" tab="'. $k .'">'. $k .'</div>';
                    }
                    
                    foreach ($arrProfileKeys as $k)
                    {
                        if ($k == 'cache')
                        {
                            echo '
                            <div class="hathoora_section_tab_content" tab="'. $k .'">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="hathoora_profile_num">#</th>
                                            <th class="hathoora_profile_took">Time&nbsp;(msec)</th>
                                            <th class="hathoora_profile_name">Pool</th>
                                            <th class="hathoora_profile_name">Method</th>
                                            <th class="hathoora_profile_message">Key</th>
                                            <th class="hathoora_profile_name">Status</th>
                                            <th class="hathoora_profile_took">Took&nbsp;(msec)</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

                            $_arr = $arrProfile[$k];
                            if (is_array($_arr))
                            {
                                $i = 0;
                                foreach ($_arr as $k => $_arrProfile)
                                {
                                    $i++;
                                    $class = 'even';
                                    if ($k % 2 == 0)
                                        $class = 'odd';
                                    
                                    $start = $_arrProfile['start'];
                                    $end = $_arrProfile['end'];
                                    
                                    $took = number_format(round(\hathoora\logger\profiler::microtimeDiff($start, $end) * 1000, 3), 3);
                                    
                                    echo '
                                    <tr class="'. $class .'">
                                        <td>'. $i .'</td>
                                        <td class="n">'. number_format(round(\hathoora\logger\profiler::microtimeDiff(HATHOORA_PROFILE_START_TIME, $start) * 1000, 3), 3) .'</td>
                                        <td>'. $_arrProfile['poolName'] .'</td>
                                        <td>'. $_arrProfile['method'] .'</td>
                                        <td>'. $_arrProfile['name'] .'</td>
                                        <td style="padding-right:10px;">'. $_arrProfile['status'] .'</td>
                                        <td class="n">'. $took .'</td>
                                    </tr>';
                                }
                            }
                            else
                            {
                                echo '
                                <tr>
                                    <td colspan="5">Nothing is being profiled.</td>
                                </tr>';                                            
                            }
                            
                            echo '  </tbody>
                                </table>
                            </div>';
                        }                    
                        else if ($k == 'db')
                        {
                            echo '
                            <div class="hathoora_section_tab_content" tab="'. $k .'">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="hathoora_profile_num">#</th>
                                            <th class="hathoora_profile_time">Time&nbsp;(msec)</th>
                                            <th class="hathoora_profile_name">DSN</th>
                                            <th class="hathoora_profile_message">Query</th>
                                            <th class="hathoora_profile_took">Took&nbsp;(msec)</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

                            $_arr = $arrProfile[$k];
                            if (is_array($_arr))
                            {
                                $i = 0;
                                foreach ($_arr as $k => $_arrProfile)
                                {
                                    $i++;
                                    $class = 'even';
                                    if ($k % 2 == 0)
                                        $class = 'odd';
                                    
                                    $start = $_arrProfile['start'];
                                    $end = $_arrProfile['end_query'];
                                    $end_execution = isset($_arrProfile['end_execution']) ? $_arrProfile['end_execution'] : null;
                                    
                                    $query_time = number_format(round(\hathoora\logger\profiler::microtimeDiff($start, $end) * 1000, 3), 3);
                                    $execution_time = false;
                                    if ($end_execution)
                                        $execution_time = number_format(round(\hathoora\logger\profiler::microtimeDiff($start, $end_execution) * 1000, 3), 3);
                                    $error = isset($_arrProfile['error']) ? $_arrProfile['error'] : null;
                                    if ($error)
                                        $error = '<div class="hathoora_profile_error">'. $error .'</div>';
                                    
                                    echo '
                                    <tr class="'. $class .'">
                                        <td>'. $i .'</td>
                                        <td class="n">'. number_format(round(\hathoora\logger\profiler::microtimeDiff(HATHOORA_PROFILE_START_TIME, $start) * 1000, 3), 3) .'</td>
                                        <td>'. $_arrProfile['dsn_name'] .'</td>
                                        <td style="padding-right:10px;">'. nl2br(htmlentities($_arrProfile['query'])) . $error .'</td>
                                        <td class="n">'. $query_time . ($execution_time ? '<br/><span>('. $execution_time .')' : '') .'</td>
                                    </tr>';
                                }
                            }
                            else
                            {
                                echo '
                                <tr>
                                    <td colspan="5">Nothing is being profiled.</td>
                                </tr>';                                            
                            }
                            
                            echo '  </tbody>
                                </table>
                            </div>';
                        }
                        else if ($k == 'template')
                        {
                            echo '
                            <div class="hathoora_section_tab_content" tab="'. $k .'">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="hathoora_profile_num">#</th>
                                            <th class="hathoora_profile_time">Time&nbsp;(msec)</th>
                                            <th>Name</th>
                                            <th class="hathoora_profile_num">Cached</th>
                                            <th class="hathoora_profile_took">Took&nbsp;(msec)</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

                            $_arr = $arrProfile[$k];
                            if (is_array($_arr))
                            {
                                $i = 0;
                                foreach ($_arr as $k => $_arrProfile)
                                {
                                    $i++;
                                    $class = 'even';
                                    if ($k % 2 == 0)
                                        $class = 'odd';
                                    
                                    $start = $_arrProfile['start'];
                                    $end = $_arrProfile['end'];
                                    
                                    $time = number_format(round(\hathoora\logger\profiler::microtimeDiff($start, $end) * 1000, 3), 3);
                                    
                                    echo '
                                    <tr class="'. $class .'">
                                        <td>'. $i .'</td>
                                        <td class="n">'. number_format(round(\hathoora\logger\profiler::microtimeDiff(HATHOORA_PROFILE_START_TIME, $start) * 1000, 3), 3) .'</td>
                                        <td>'. $_arrProfile['name'] .'</td>
                                        <td>'. $_arrProfile['cached'] .'</td>
                                        <td class="n">'. $time .'</td>
                                    </tr>';
                                }
                            }
                            else
                            {
                                echo '
                                <tr>
                                    <td colspan="5">Nothing is being profiled.</td>
                                </tr>';                                            
                            }
                            
                            echo '  </tbody>
                                </table>
                            </div>';
                        }                    
                    }
                }
                
            ?>
        </div>
    </div>
</div>