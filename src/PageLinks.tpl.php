<?php

    use QCubed\Plugin\PageLinks;

    $data = $this->getPreparedData();
    $mode = $data['mode'];
    $ungrouped = $data['ungrouped'] ?? [];
    $groups = $data['groups'] ?? [];

    $renderList = function (array $items) {
        if (!$items) {
            return;
        }
?>
<ul class="links-list">
<?php foreach ($items as $row) { ?>
    <li><a href="<?= htmlspecialchars($row['href'], ENT_QUOTES, 'UTF-8'); ?>"<?php if (!empty($row['target'])) { ?> target="<?= htmlspecialchars($row['target'], ENT_QUOTES, 'UTF-8'); ?>" rel="noopener noreferrer"<?php } ?>><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
<?php } ?>
</ul>
        <?php
    };

    if ($mode === PageLinks::MODE_ATTACHMENT_GROUPS) {
        $attachmentGroups = $data['attachment_groups'] ?? [];
        $isExpandable = $this->Expandable;
        $limit = (int)$this->LimitCount;
        ?>
<div id="<?= $this->ControlId; ?>" class="page-links-groups js-page-links-groups" data-limit="<?= $limit; ?>" data-page="0">
<?php foreach ($attachmentGroups as $group) { ?>
    <div class="page-links-group-item" data-group-id="<?= (int)$group['id']; ?>">
        <span class="col-category"><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="col-links">
<?php foreach ($group['items'] as $item) {
    $name = trim((string)($item['name'] ?? ''));
    $href = trim((string)($item['href'] ?? ''));

    if ($name === '') {
        continue;
    }
    ?>
<?php _nl('<span class="col-link">'); if ($href !== '') { ?>
            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></a>
<?php } else { ?><?= htmlspecialchars(_indent($name, 6), ENT_QUOTES, 'UTF-8'); ?><?php } ?>
<?php _nl('</span>'); } ?>
<?php _nl('</span>'); ?>

    </div>
<?php } ?>
<?php if ($isExpandable && $limit > 0) { ?>
    <div class="page-links-groups-nav">
        <a class="page-links-groups-btn js-page-links-reset is-hidden" href="#"><?= htmlspecialchars($this->ResetLabel, ENT_QUOTES, 'UTF-8'); ?></a>
        <a class="page-links-groups-btn js-page-links-more" href="#"><?= htmlspecialchars($this->MoreLabel, ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
<?php } ?>
</div>
        <?php
        return;
    }

    if ($mode === PageLinks::MODE_FLAT) {
        $flatItems = $ungrouped;

        foreach ($groups as $groupItems) {
            foreach ($groupItems as $item) {
                $flatItems[] = $item;
            }
        }

        $renderList($flatItems);
        return;
    }

if ($mode === PageLinks::MODE_GROUPED) {
foreach ($groups as $groupTitle => $groupItems) { ?>
<div class="links-group-title">
    <h1><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
</div>
<?php
$renderList($groupItems);
}

return;
}

$renderList($ungrouped);

foreach ($groups as $groupTitle => $groupItems) { ?>

<div class="links-group-title">
    <h1><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
</div>
<?php $renderList($groupItems); ?>
<?php } ?>