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
    $objContentMediaRender->EmptyMediaUrl = FRONTEND_HELPERS_ASSETS_URL . "/images/no-media-300-175.png";
    $objContentMediaRender->UseWrapper = false;
    ?>
    <a href="<?php _p($news->getTitleSlug(), false) ?>">
        <article class="news-card">
<?php $objContentMediaRender->render(); ?>
            <h3><?php _p($news->getTitle(), false); if ($news->getChangesId()) { ?><span class="news-card-change"><?php _p($news->getChanges(), false); ?></span><?php } ?></h3>
        </article>
            </a>
    <?php } ?>
</section>

