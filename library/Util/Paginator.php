<?php

/**
 * An utility class for paginating result sets.
 * Feed it the number of items per page you would like to see,
 * and set a page. Fetch results using the offset and limit
 * that this paginator calculates for you. Then set the total
 * number of results in this paginator, and it will tell you
 * if the current page is out of bounds or not.
 */
final class ZFE_Util_Paginator
{
    protected $page = 1;
    protected $pages = 1;

    protected $itemsPerPage = 5;
    protected $totalItems = 0;

    /**
     * Simple constructor, sole argument is the number of
     * items to show on one page
     */
    public function __construct($itemsPerPage = 5)
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * Sets the total, then recalculates some variables,
     * and lets us know whether the currently set page
     * was out of bounds or not.
     */
    public function setTotal($total)
    {
        $this->totalItems = $total;

        return $this->recalculate();
    }

    /**
     * Set the desired page to view
     */
    public function setPage($page) 
    {
        $page = intval($page);
        $this->page = max(1, $page);

        return $this;
    }

    /**
     * Calculates the offset for database queries
     */
    public function getOffset()
    {
        return $this->itemsPerPage * ($this->page - 1);
    }

    /**
     * Returns the limit for database queries
     */
    public function getItems()
    {
        return $this->itemsPerPage;
    }

    /**
     * Collects relevant information for the Pagination
     * view helper. This information is to be set in the
     * view's 'pageInfo' variable
     */
    public function getInfo()
    {
        return array(
            'page' => $this->page,
            'pages' => $this->pages,
            'perPage' => $this->itemsPerPage,
            'total' => $this->totalItems
        );
    }

    /**
     * Recalculates the number of pages required to browse
     * through the whole result set.
     * Returns true if page was out of bounds, otherwise false.
     */
    protected function recalculate()
    {
        // Round up to the next whole number
        $this->pages = intval(ceil(1.0 * $this->totalItems / $this->itemsPerPage));

        if ($this->page > $this->pages) {
            $this->page = $this->pages;
            return true;
        }

        return false;
    }
}
