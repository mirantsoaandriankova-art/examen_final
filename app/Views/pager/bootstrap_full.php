<?php

/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Navigation des pages" class="d-flex justify-content-center">
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $pager->hasPrevious() ? '' : 'disabled' ?>">
            <a class="page-link" href="<?= $pager->hasPrevious() ? $pager->getPrevious() : '#' ?>" aria-label="Page précédente">&laquo;</a>
        </li>
        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
            </li>
        <?php endforeach; ?>
        <li class="page-item <?= $pager->hasNext() ? '' : 'disabled' ?>">
            <a class="page-link" href="<?= $pager->hasNext() ? $pager->getNext() : '#' ?>" aria-label="Page suivante">&raquo;</a>
        </li>
    </ul>
</nav>
