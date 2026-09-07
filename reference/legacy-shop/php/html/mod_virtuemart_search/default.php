<?php // no direct access
defined('_JEXEC') or die('Restricted access'); ?>
<!--BEGIN Search Box -->
<form action="<?php echo JRoute::_('index.php?option=com_virtuemart&view=category&search=true&limitstart=0&virtuemart_category_id='.$category_id ); ?>" method="get">
<div class="search<?php echo $params->get('moduleclass_sfx'); ?>">
    <?php
    // 1) Das Such-Input
    $output = '<input '
        . 'name="keyword" '
        . 'id="mod_virtuemart_search" '
        . 'maxlength="60" '
        . 'placeholder="'. htmlspecialchars($text, ENT_QUOTES) .'" '
        . 'class="inputbox'. $moduleclass_sfx .'" '
        . 'type="text" '
        . 'size="60"'
        . ' />';

    if ($button) :
        // 2) Unser neues FA-Button-Markup mit direkter Variablen-Einbindung
        $label = htmlspecialchars($button_text, ENT_QUOTES);
        $button = '<button'
            . ' type="submit"'
            . ' class="button-topsearch"'
            . ' onclick="this.form.keyword.focus();"'
            . ' aria-label="'. $label .'">'
            . '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>'
            . '</button>';

        // 3) Button-Positionierung
        switch ($button_pos) :
            case 'top':
                $output = $button . '<br />' . $output;
                break;
            case 'bottom':
                $output = $output . '<br />' . $button;
                break;
            case 'right':
                $output = $output . $button;
                break;
            case 'left':
            default:
                $output = $button . $output;
                break;
        endswitch;
    endif;

    // 4) Ausgabe
    echo $output;
    ?>

</div>
		<input type="hidden" name="limitstart" value="0" />
		<input type="hidden" name="option" value="com_virtuemart" />
		<input type="hidden" name="view" value="category" />
		<input type="hidden" name="virtuemart_category_id" value="<?php echo $category_id; ?>"/>
<?php if(!empty($set_Itemid)){
	echo '<input type="hidden" name="Itemid" value="'.$set_Itemid.'" />';
} ?>

	  </form>

<!-- End Search Box -->