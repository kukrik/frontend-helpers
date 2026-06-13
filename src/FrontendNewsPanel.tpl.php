<?php
    use QCubed\Plugin\FrontendMediaRenderer;
?>
<section class="block block--old-news-grid">
<?php foreach (($this->DataSource ?? []) as $news) {
    $objContentMediaRender = new FrontendMediaRenderer($this);
    $objContentMediaRender->TempUrl = APP_UPLOADS_TEMP_URL . "/_files/thumbnail";

    $objContentMediaRender->MediaTypeId = $news->getMediaTypeId();
    $objContentMediaRender->ContentCoverMediaId = $news->getContentCoverMediaId();
    $objContentMediaRender->RequireMedia = true;
    $objContentMediaRender->EmptyMediaUrl = FRONTEND_HELPERS_ASSETS_URL . "/images/no-image-660-365.jpg";
    $objContentMediaRender->UseWrapper = false;
    ?>
    <a href="<?= $news->getTitleSlug() ?>">
        <article class="news-card"><?= _indent(_r($objContentMediaRender), 5); ?>
            <h3><?= $news->getTitle(); if ($news->getChangesId()) { ?><span class="news-card-change"><?= $news->getChanges(); ?></span><?php } ?></h3>
        </article>
    </a>
<?php } ?>
</section>

