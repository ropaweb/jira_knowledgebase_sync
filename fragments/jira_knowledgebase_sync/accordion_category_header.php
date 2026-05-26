<?php
    /** @var int $counter_i */
    $counter_i = $this->getVar('counter_i');

    /** @var string $category_name */
    $category_name = $this->getVar('category_name');
?>



    <h2 class="accordion-header"
        id="heading-General-<?= $counter_i ?>">
        <button
            class="accordion-button main-accordion collapsed"
            type="button" data-bs-toggle="collapse"
            data-bs-target="#collapseGeneral-<?= $counter_i ?>"
            aria-expanded="true"
            aria-controls="collapseGeneral-<?= $counter_i ?>">
            <?= $category_name ?>
        </button>
    </h2>
