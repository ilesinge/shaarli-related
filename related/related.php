<?php

use Shaarli\Config\ConfigManager;
use Shaarli\Plugin\PluginManager;


/**
 * Initialization function.
 * It will be called when the plugin is loaded.
 * This function can be used to return a list of initialization errors.
 *
 * @param $conf ConfigManager instance.
 *
 * @return array List of errors (optional).
 */
function related_init($conf)
{

    global $routerClass;
    global $newLinkDb;
    if (class_exists('Shaarli\Legacy\LegacyRouter')) {
        $routerClass = 'Shaarli\Legacy\LegacyRouter';
        $newLinkDb = true;
    }
    else
    {
        $routerClass = 'Shaarli\Router';
        $newLinkDb = false;
    }

    if (! $conf->exists('translation.extensions.related')) {
        // Custom translation with the domain 'demo'
        $conf->set('translation.extensions.related', 'plugins/related/languages/');
        $conf->write(true);
    }

    return [];
}



/**
 * In the footer hook, there is a working example of a translation extension for Shaarli.
 *
 * The extension must be attached to a new translation domain (i.e. NOT 'shaarli').
 * Use case: any custom theme or non official plugin can use the translation system.
 *
 * See the documentation for more information.
 */
const EXT_TRANSLATION_DOMAIN = 'related';

/*
 * This is not necessary, but it's easier if you don't want Poedit to mix up your translations.
 */
function related_plugin_t($text, $nText = '', $nb = 1)
{
    return t($text, $nText, $nb, EXT_TRANSLATION_DOMAIN);
}

$relatedBookmarkTags = [];

/**
 * Get the bookmark list in a backward-compatible fashion.
 * 
 * < 0.12 use the global $linkDb variable
 * >= 0.12 use the bookmarkService
 * 
 * @return array Bookmark list
 */
function get_bookmarks()
{
    global $newLinkDb;
    global $relatedBookmarkTags;
    if ($newLinkDb)
    {
        global $app;
        /** @var \Shaarli\Container\ShaarliContainer $container */
        $container = $app->getContainer();
        $bookmarkService = $container->bookmarkService;
        $bookmarks = $bookmarkService->search([], 'all');
        $formatter = $container->formatterFactory->getFormatter();
        $flatBookmarks = [];

        if (method_exists($bookmarks, 'getBookmarks'))
        {
            $bookmarks = $bookmarks->getBookmarks();
        }

        foreach($bookmarks->getBookmarks() as $bookmark)
        {
            $flatBookmarks[$bookmark->getId()] = (array)$formatter->format($bookmark);
            foreach ($bookmark->getTags() as $tag) {
                $relatedBookmarkTags[$tag][] = $bookmark->getId();
            }
        }
        return $flatBookmarks;
    }
    else
    {
        // Legacy method to access bookmarks
        global $linkDb;

        foreach ($linkDb as $link) {
            foreach (explode(' ', $link['tags']) as $tag) {
                $relatedBookmarkTags[$tag][] = $link['id'];
            }
        }

        return $linkDb;
    }
}

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
    global $relatedBookmarkTags;

    $theme = $conf->get('resource.theme');

    // @TODO use proper templating system
    $html = file_get_contents(PluginManager::$PLUGINS_PATH . '/related/related.html');
    $link_html = file_get_contents(PluginManager::$PLUGINS_PATH . '/related/related_link.html');

    $linkDb = get_bookmarks();
    foreach ($data['links'] as &$value) {
        $current_tags = explode(' ', $value['tags']);
        $related = [];

        foreach ($current_tags as $tag) {
            if (array_key_exists($tag, $relatedBookmarkTags)) {
                foreach ($relatedBookmarkTags[$tag] as $bookmarkId) {
                    if ($bookmarkId === $value['id'] || ($linkDb[$bookmarkId]['private'] && true !== $data['_LOGGEDIN_'])) {
                        continue;
                    }

                    // @TODO config minimum number of identical tags
                    if (!array_key_exists($bookmarkId, $related)) {
                        $related[$bookmarkId] = $linkDb[$bookmarkId];
                        $related[$bookmarkId]['count'] = 0;
                    }
                    $related[$bookmarkId]['count']++;
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
        if (empty($related)) {
            $list_items = "<li>".related_plugin_t('No related link')."</li>";
        }
        else {
            foreach ($related as $related_link) {
                $description = html_entity_decode($related_link['description']);
                $description = strip_tags($description);
                // @TODO config description length
                $description_length = 150;
                $description = mb_strlen($description) > $description_length ? mb_substr($description, 0, $description_length)."..." : $description;
                $description = htmlentities($description);

                // @TODO Add config to switch URL <=> shorturl
                global $newLinkDb;
                if ($newLinkDb) {
                    $link = $data['_BASE_PATH_'].'/shaare/'.$related_link['shorturl'];
                }
                else {
                    $link = '?'.$related_link['shorturl'];
                }
                $list_items .= sprintf($link_html,
                    $link,
                    $related_link['title'],
                    $description
                );
            }
        }

        $link_plugin = sprintf($html,
            $value['id'],
            $data['_BASE_PATH_'],
            related_plugin_t('See related links'),
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
    global $routerClass;
    if ($data['_PAGE_'] == $routerClass::$PAGE_LINKLIST) {
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
    global $routerClass;
    if ($data['_PAGE_'] == $routerClass::$PAGE_LINKLIST) {
        $data['js_files'][] = PluginManager::$PLUGINS_PATH . '/related/related.js';
    }

    return $data;
}
