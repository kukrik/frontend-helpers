<?php if (!$this->hasEvents()) { ?>
    <div class="sports-calendar-empty"><?= $this->NoEventsText; ?></div>
<?php } else { ?>
<?php foreach ($this->GroupedEvents as $arrGroup) { ?>
    <div class="sports-calendar-group">
<?php if (!empty($arrGroup['title'])) { ?>
    <h3 class="sports-calendar-group-title"><?= $arrGroup['title']; ?></h3>
<?php } ?>
    <div class="sports-calendar-table">
<?php foreach ($arrGroup['events'] as $objEvent) { ?>
<?php
    $hasModalContent = $this->hasModalContent($objEvent);
    $tag = $hasModalContent ? 'a' : 'span';
    $objEventUrl = $objEvent->getTitleSlug();
    $date = $this->renderEventDate($objEvent);
    $place = $objEvent->getEventPlace() ?? '';
?>
        <<?= $tag; ?> class="sports-calendar-table-row <?= !$hasModalContent ? 'is-disabled' : ''; ?>"<?php if ($hasModalContent) { ?> data-id="<?= (int)$objEvent->getId(); ?>" href="<?= $objEventUrl; ?>"<?php } ?>>
            <span class="sports-calendar-table-date"><?= $date; ?></span>
            <span class="sports-calendar-table-title"><?= $objEvent->getTitle(); ?>
<?php $changeText = $this->renderEventChange($objEvent); ?>
<?php if ($changeText) { ?>
                <span class="sports-calendar-change"><?= $changeText; ?></span>
<?php } ?>
<?php $attachmentLabels = $this->getAttachmentTypeLabels((int)$objEvent->getId()); ?>
<?php if ($attachmentLabels) { ?>

                <span class="sports-calendar-meta"><?= implode(' • ', $attachmentLabels); ?></span><?php } ?>

            </span>
            <span class="sports-calendar-table-place"><?= $place; ?></span>
        </<?= $tag; ?>>
<?php } ?>
    </div>
<?php } ?>

<?php } ?>

