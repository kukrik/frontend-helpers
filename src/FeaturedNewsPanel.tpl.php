<?php

    require_once __DIR__ . '/FrontendMediaRenderer.php';

    $news = $this->DataSource[0] ?? null;
    if (!$news) { return; }

    $objContentMediaRender = new FrontendMediaRenderer($this);
    $objContentMediaRender->TempUrl = APP_UPLOADS_TEMP_URL . "/_files/thumbnail";

    $objContentMediaRender->MediaTypeId = $news->getMediaTypeId();
    $objContentMediaRender->ContentCoverMediaId = $news->getContentCoverMediaId();
    $objContentMediaRender->RequireMedia = true;
    $objContentMediaRender->EmptyMediaUrl = FRONTEND_URL . "/assets/images/no-media-300-175.png";
    $objContentMediaRender->UseWrapper = false;

    if ($news->getChangesId()) {
        $objFeaturedTime = $news->getPostUpdateDate()->qFormat('YYYY-MM-DD');
        $objFeaturedDate = $news->getPostUpdateDate()->qFormat('DD.MM.YYYY');
    } else {
        $objFeaturedTime = $news->getPostDate()->qFormat('YYYY-MM-DD');
        $objFeaturedDate = $news->getPostDate()->qFormat('DD.MM.YYYY');
    }
?>

<section class="block block--featured">
    <article class="featured-item">
        <a href="<?php _p($news->getTitleSlug(), false) ?>" class="featured-link">
            <div class="featured-content">
                <h2 class="featured-title"><?php _p($news->getTitle(), false) ?></h2>
                <time class="featured-date" datetime="<?php _p($objFeaturedTime, false) ?>"><?php _p($objFeaturedDate, false); if ($news->getChangesId()) { ?>

                    <span class="featured-change"><?php _p(_indent($news->getChanges(), 12), false); ?>

                    </span><?php } ?>

                </time>
                <div class="featured-excerpt">
                    <?php _p($news->getContent(), false) ?>
                </div>
            </div>
            <div class="featured-media">
                <?php $objContentMediaRender->render(); ?>
            </div>
        </a>
    </article>
</section>


