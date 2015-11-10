<?php

/**
 * The Paginator view helper
 *
 * Based on the pageInfo variable in the view, this view helper will create
 * the basic HTML structure for page links, together with the previous, next,
 * first page and last page links.
 *
 * This helper will use the pagination CSS classes as used by the Twitter
 * Bootstrap framework. Support for other frameworks may be added. Configurable
 * class names may be implemented.
 *
 * TODO Based on a function argument, this view helper will create a list of
 * page numbers, limit to show only a range around the current page number,
 * or show an input field that shows the current page number, but allows the
 * user to enter any page number.
 */
class ZFE_View_Helper_Paginator extends Zend_View_Helper_Abstract
{
    protected static $style = array(
        'first' => '&lt;&lt;',
        'prev' => '&lt;',
        'next' => '&gt;',
        'last' => '&gt;&gt;'
    );

    public function paginator($pageInfo, $options = array())
    {
        // TODO: find a more universal method
        try {
            $currentUrl = $this->view->url(); // fails on routes with parameters (... is not defined)
        } catch (Exception $e) {
            $currentUrl = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri(); // loses custom routes, keeps GET params (which interfere with the ?p= param)
        }
        //$currentUrl = ""; // does not work when inserted in a different page
        $default = array(
            'maxEntries' => 11,
            'inputField' => false,
            'x_of_y_text' => 'Page %s of %d',
            'url' => $currentUrl
        );

        $options = array_merge($default, $options);
        $baseUrl = $options['url'];

        $useInput = $options['inputField'];

        // Some handy booleans
        $firstPage = $pageInfo['page'] == 1;
        $lastPage = $pageInfo['page'] == $pageInfo['pages'];

        $halfPoint = floor($options['maxEntries'] / 2);
        $start = $pageInfo['page'] > $halfPoint ? $pageInfo['page'] - $halfPoint : 1;
        $end = $start + $options['maxEntries'] - 1;

        if ($end > $pageInfo['pages']) $end = $pageInfo['pages'];
        if ($end - $start < $options['maxEntries']) $start = max(1, $end - $options['maxEntries'] + 1);

        $html = '<ul class="pagination">';

        // If not on the first page, show the "go to first page" and the "go to
        // previous page" links.
        if (!$firstPage) {
            $html .= '<li class="first">';
            $url = $baseUrl . '?' . http_build_query(array('p' => 1));
            $html .= '<a href="' . $url . '" data-page="1" rel="nofollow">' . static::$style['first'] . '</a>';
            $html .= '</li>';

            $html .= '<li class="previous">';
            $url = $baseUrl . '?' . http_build_query(array('p' => $pageInfo['page'] - 1));
            $html .= '<a href="' . $url . '" data-page="' . ($pageInfo['page'] - 1) . '" rel="nofollow">' . static::$style['prev'] . '</a>';
            $html .= '</li>';

            if (!$useInput && $start > 1) $html .= '<li class="disabled"><span>...</span></li>';
        }

        // Depending on the mode, add page number links, or add an input field
        if ($useInput) {
            $len = strlen($pageInfo['pages']);

            $fld = '<input type="text" size="' . $len . '" maxlength="' . $len . '" value="' . $pageInfo['page'] . '" />';
            $html .= '<li><span>' . vsprintf($options['x_of_y_text'], array($fld, $pageInfo['pages'])) . '</span></li>';
        } else {
            // Add the page number links, and make the current one active
            for($page = $start; $page <= $end; $page++) {
                $url = $baseUrl . '?' . http_build_query(array('p' => $page));

                $cls = array();
                if ($page == $pageInfo['page']) $cls[] = 'active';

                $html .= '<li class="' . implode(' ', $cls) . '">';
                $html .= '<a href="' . $url . '" data-page="' . $page . '" rel="nofollow">' . $page . '</a>';
                $html .= '</li>';
            }
        }

        // If not on the last page, show the "go to next page" and "go to last
        // page" links.
        if (!$lastPage) {
            if (!$useInput && $end < $pageInfo['pages']) $html .= '<li class="disabled"><span>...</span></li>';

            $html .= '<li class="next">';
            $url = $baseUrl . '?' . http_build_query(array('p' => $pageInfo['page'] + 1));
            $html .= '<a href="' . $url . '" data-page="' . ($pageInfo['page'] + 1) . '" rel="nofollow">' . static::$style['next'] . '</a>';
            $html .= '</li>';

            $html .= '<li class="last">';
            $url = $baseUrl . '?' . http_build_query(array('p' => $pageInfo['pages']));
            $html .= '<a href="' . $url . '" data-page="' . $pageInfo['pages'] . '" rel="nofollow">' . static::$style['last'] . '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }
}
