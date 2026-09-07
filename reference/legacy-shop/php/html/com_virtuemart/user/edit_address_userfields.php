<?php
/**
 *
 * Modify user form view, User info
 *
 * @package     VirtueMart
 * @subpackage  User
 * @author      Oscar van Eijk, Eugen Stranz, Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2019 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: edit_address_userfields.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Status Of Delimiter / Wrapper
$closeDelimiter = false;
$openGroup = true;
$hiddenFields = '';

$i = 0;
// When only one delimiter exists, set it to begin of the array
$tmp = false;
foreach ($this->userFields['fields'] as $k => $field) {
    if ($field['type'] == 'delimiter') {
        $tmp = $field;
        $pos = $k;
        $i++;
    }
    if ($i > 1) {
        $tmp = false;
        break;
    }
}

if ($tmp) {
    unset($this->userFields['fields'][$pos]);
    array_unshift($this->userFields['fields'], $tmp);
}

// Output: Userfields
foreach ($this->userFields['fields'] as $field) {

    if ($field['type'] == 'delimiter') {

        // Vorherige Gruppe sauber schließen
        if ($closeDelimiter) {
            if (!$openGroup) {
                echo '</div>';
                $openGroup = true;
            }
            echo '</fieldset>';
            $closeDelimiter = false;
        } elseif (!$openGroup) {
            echo '</div>';
            $openGroup = true;
        }

        if ($field['name'] == 'delimiter_userinfo') {
            if ($this->getLayout() == 'edit') {
                echo $this->loadTemplate('vmshopper');
            }

            // Für delimiter_userinfo kein fieldset wie im Original
            $closeDelimiter = false;
            $openGroup = true;

        } else {
            ?>
            <fieldset class="vm-userfield-fieldset">
                <!--<legend class="userfields_info"><?php //echo $field['title']; ?></legend>-->
            <?php

            $closeDelimiter = true;
            $openGroup = true;
        }

    } elseif (!empty($field['hidden'])) {

        // Hidden fields sammeln und ganz am Ende ausgeben
        $hiddenFields .= $field['formcode'] . "\n";

    } else {

        // Neue Gruppe starten
        if ($openGroup) {
            $openGroup = false;
            echo '<div class="vm-userfields-group">';
        }

        $descr = empty($field['description']) ? $field['title'] : $field['description'];
        ?>
        <div class="vm-userfield-row <?php echo htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(strip_tags($descr), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="vm-userfield-label">
                <label class="<?php echo $field['name']; ?>" for="<?php echo $field['name']; ?>_field">
                    <?php echo $field['title'] . ($field['required'] ? ' <span class="asterisk">*</span>' : ''); ?>
                </label>
            </div>

            <div class="vm-userfield-input">
                <?php echo $field['formcode']; ?>
            </div>
        </div>
        <?php
    }
}

// Am Ende offene Gruppe / Fieldset schließen
if (!$openGroup) {
    echo '</div>';
}

if ($closeDelimiter) {
    echo '</fieldset>';
    $closeDelimiter = false;
}
?>

<div class="vm-newsletter-row">
    <div class="vm-newsletter-check">
        <input type="checkbox" name="newsletter_subscribe" id="newsletter_subscribe" value="1" checked />
    </div>
    <div class="vm-newsletter-label">
        <label for="newsletter_subscribe">
            <?php echo JText::_('USER_VMREGIST_NEWSLETTER'); ?>
        </label>

    </div>
</div>

<script>
jQuery('#first_name_field').focusout(function () {
    var firstname = jQuery('#first_name_field').val();
    var lastname = jQuery('#last_name_field').val();
    var name = firstname + ' ' + lastname;
    jQuery('#name_field').val(name);
}).focusout();

jQuery('#last_name_field').focusout(function () {
    var firstname = jQuery('#first_name_field').val();
    var lastname = jQuery('#last_name_field').val();
    var name = firstname + ' ' + lastname;
    jQuery('#name_field').val(name);
}).focusout();
</script>

<?php echo $hiddenFields; ?>