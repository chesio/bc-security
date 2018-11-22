<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Tools;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-tools';


    /**
     */
    public function __construct()
    {
        $this->page_title = _x('Tools', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Tools', 'Dashboard menu item name', 'bc-security');
    }


    public function printContents()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '</div>';
    }
}
