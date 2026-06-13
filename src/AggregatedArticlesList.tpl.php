<?php

    use QCubed\Plugin\AggregatedArticlesList;

    $data = $this->getPreparedData();
    $mode = $data['mode'];
    $items = $data['items'];
    $yearsPerPage = (int)($data['years_per_page'] ?? 5);

    if (!$items) {
        return;
    }
?>

<?php if ($mode === AggregatedArticlesList::MODE_YEARS) { ?>
    <div id="<?= $this->ControlId; ?>" class="aggregated-articles-years js-aggregated-years" data-limit="<?= $yearsPerPage; ?>" data-page="0">
    <ul class="links-list">
<?php foreach ($items as $row) { ?>
        <li class="aggregated-year-item" data-year="<?= (int)$row['year']; ?>">
            <a href="<?= htmlspecialchars($row['href'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
<?php } ?>
    </ul>
    <div class="aggregated-years-nav">
        <a class="aggregated-years-btn js-aggregated-years-newer is-hidden" href="#"><?= $this->strShowNewerYearsLabel; ?></a>
        <a class="aggregated-years-btn js-aggregated-years-older" href="#"><?= $this->strShowOlderYearsLabel; ?></a>
    </div>
</div>
<?php } else { ?>
    <ul class="links-list">
<?php foreach ($items as $row) { ?>
    <li>
        <a href="<?= htmlspecialchars($row['href'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
    </li>
<?php } ?>
    </ul>
<?php } ?>