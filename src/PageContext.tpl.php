<?php
    $items = $this->getNormalizedItems();
    $title = $this->Title ?? '';
    $emptyText = $this->EmptyText ?? '';
?>
<div class="page-content-wrapper">
    <h1><?php echo htmlspecialchars($title, ENT_QUOTES); ?></h1>

    <?php if (!$items) { ?>
        <div class="context-info">
            <h2 class="context-title">
                <?php echo htmlspecialchars($emptyText, ENT_QUOTES); ?>
            </h2>
        </div>
    <?php } else { ?>
        <?php foreach ($items as $row) { ?>
            <div class="context-info">
                <a href="<?php echo htmlspecialchars($row['url'], ENT_QUOTES); ?>">
                    <h2 class="context-title">
                        <?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>
                    </h2>

                    <?php if (!empty($row['date'])) { ?>
                        <div class="context-time">
                            <?php echo htmlspecialchars($row['date'], ENT_QUOTES); ?>
                        </div>
                    <?php } ?>
                </a>
            </div>
        <?php } ?>
    <?php } ?>
</div>
