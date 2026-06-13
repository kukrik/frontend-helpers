<?php

    use QCubed\Plugin\FrontendMediaRenderer;

    $news = $this->DataSource[0] ?? null;
    if (!$news) { return; }

    $this->objSiteOptions = SiteOptions::load(1);

    $objContentMediaRender = new FrontendMediaRenderer($this);
    $objContentMediaRender->TempUrl = APP_UPLOADS_TEMP_URL . "/_files/thumbnail";

    $objContentMediaRender->MediaTypeId = $news->getMediaTypeId();
    $objContentMediaRender->ContentCoverMediaId = $news->getContentCoverMediaId();
    $objContentMediaRender->RequireMedia = true;
    $objContentMediaRender->EmptyMediaUrl = FRONTEND_HELPERS_ASSETS_URL . "/images/no-image-660-365.jpg";
    $objContentMediaRender->UseWrapper = false;

    if ($news->getChangesId()) {
        $objFeaturedTime = $news->getPostUpdateDate()->qFormat('YYYY-MM-DD');
        $objFeaturedDate = $news->getPostUpdateDate()->qFormat($this->objSiteOptions->DefaultDateFormatObject->Date);
    } else {
        $objFeaturedTime = $news->getPostDate()->qFormat('YYYY-MM-DD');
        $objFeaturedDate = $news->getPostDate()->qFormat($this->objSiteOptions->DefaultDateFormatObject->Date);
    }
?>

<section class="block block--featured">
    <article class="featured-item">
        <div class="featured-link js-clickable-card" data-href="<?= $news->getTitleSlug() ?>" tabindex="0" role="link">
            <div class="featured-content">
                <h2 class="featured-title"><?= $news->getTitle() ?></h2>
                <time class="featured-date" datetime="<?= $objFeaturedTime ?>"><?= $objFeaturedDate; if ($news->getChangesId()) { ?>

                        <span class="featured-change"><?= _indent($news->getChanges(), 12); ?>

                        </span><?php } ?></time>
                <div class="featured-excerpt">
                    <?= $news->getContent() ?>
                </div>
            </div>
            <div class="featured-media"><?= _indent(_r($objContentMediaRender), 6); ?>
            </div>
        </div>
    </article>
</section>