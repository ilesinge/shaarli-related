<?php

/**
 * Hook render_linklist.
 *
 * Template placeholders:
 *   - action_plugin: next to 'private only' button.
 *   - plugin_start_zone: page start
 *   - plugin_end_zone: page end
 *   - link_plugin: icons below each links.
 *
 * Data:
 *   - _LOGGEDIN_: true/false
 *
 * @param array $data data passed to plugin
 * @param $conf ConfigManager instance.
 *
 * @return array altered $data.
 */
function hook_related_render_linklist($data, $conf)
{
    $theme = $conf->get('resource.theme');
    
    $html = file_get_contents(PluginManager::$PLUGINS_PATH . '/related/related.html');
    $link_html = file_get_contents(PluginManager::$PLUGINS_PATH . '/related/related_link.html');
    global $linkDb;
    
    foreach ($data['links'] as &$value) {
        $current_tags = explode(' ', $value['tags']);
        $related = [];
        $count = [];
        foreach ($linkDb as $link) {
            if ($link['id'] !== $value['id']) {
                if (!$link['private'] || $data['_LOGGEDIN_']) {
                    $link_tags = explode(' ', $link['tags']);
                    $count_common = count(array_intersect($current_tags, $link_tags));
                    // @TODO config minimum number of identical tags
                    if ($count_common > 0) {
                        $link['count'] = $count_common;
                        $related[] = $link;
                    }
                }
            }
        }
        shuffle($related);
        usort($related, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        // @TODO config how many links
        $related = array_slice($related, 0, 5);
        
        $list_items = '';
        foreach ($related as $related_link) {
            $description = html_entity_decode($related_link['description']);
            // @TODO config description length
            $description_length = 150;
            $description = mb_strlen($description) > $description_length ? mb_substr($description, 0, $description_length)."..." : $description;
            $description = htmlentities($description);
            
            // @TODO Add config to switch URL <=> shorturl
            
            $list_items .= sprintf($link_html,
                '?'.$related_link['shorturl'],
                $related_link['title'],
                $description
            );
        }
        
        $link_plugin = sprintf($html,
            $value['id'],
            $value['id'],
            $list_items
        );
        $value['link_plugin'][] = $link_plugin;
    }

    $data['plugin_end_zone'][] = '<div id="related_popin" data-theme="'.$theme.'"></div>';

    return $data;
}

/**
 * Hook render_includes.
 * Executed on every page redering.
 *
 * Template placeholders:
 *   - css_files
 *
 * Data:
 *   - _PAGE_: current page
 *   - _LOGGEDIN_: true/false
 *
 * @param array $data data passed to plugin
 *
 * @return array altered $data.
 */
function hook_related_render_includes($data)
{
    // List of plugin's CSS files.
    // Note that you just need to specify CSS path.
    if ($data['_PAGE_'] == Router::$PAGE_LINKLIST) {
        $data['css_files'][] = PluginManager::$PLUGINS_PATH . '/related/related.css';
    }

    return $data;
}

/**
 * Hook render_footer.
 * Executed on every page redering.
 *
 * Template placeholders:
 *   - text
 *   - endofpage
 *   - js_files
 *
 * Data:
 *   - _PAGE_: current page
 *   - _LOGGEDIN_: true/false
 *
 * @param array $data data passed to plugin
 *
 * @return array altered $data.
 */
function hook_related_render_footer($data)
{
    // List of plugin's JS files.
    // Note that you just need to specify CSS path.
    if ($data['_PAGE_'] == Router::$PAGE_LINKLIST) {
        $data['js_files'][] = PluginManager::$PLUGINS_PATH . '/related/related.js';
    }

    return $data;
}