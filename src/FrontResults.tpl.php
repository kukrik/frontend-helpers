<?php
    $items = $this->getNormalizedItems();
    $title = $this->Title ?? '';
    $emptyText = $this->EmptyText ?? '';
?>

<h2><?php echo htmlspecialchars($title, ENT_QUOTES); ?></h2>

<?php if (!$items) { ?>
    <div class="event-item">
        <div class="no-events">
            <?php echo htmlspecialchars($emptyText, ENT_QUOTES); ?>
        </div>
    </div>
<?php } else { ?>
    <?php foreach ($items as $row) { ?>
        <li>
            <a href="<?php echo htmlspecialchars($row['url'], ENT_QUOTES); ?>" target="_blank">
                <span class="col-date">
                    <?php echo htmlspecialchars($row['date'], ENT_QUOTES); ?>
                </span>
                <span class="col-content">
                    <?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>
                </span>
            </a>
        </li>
    <?php } ?>
<?php } ?>

