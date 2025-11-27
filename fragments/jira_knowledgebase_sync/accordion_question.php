<?php
/** @var int $counter_i */
$counter_i = $this->getVar('counter_i');

/** @var int $counter_j */
$counter_j = $this->getVar('counter_j');

/** @var string $acc_name */
$acc_name = $this->getVar('acc_name');

/** @var string $acc_jiracontent */
$acc_jiracontent = $this->getVar('acc_jiracontent')?>

<!-- Zweite Ebene Accordion-->
<div class="accordion-item">
    <h2 class="accordion-header" id="generalQuestion-<?= $counter_j?>">
        <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#generalAnswer-<?= $counter_j?>"
                aria-expanded="false" aria-controls="generalAnswer-<?= $counter_j?>">
                    <b><?= $acc_name ?></b>
        </button>
    </h2>
    <div id="generalAnswer-<?= $counter_j?>"
         class="accordion-collapse collapse"
         aria-labelledby="generalQuestion-<?= $counter_j?>"
         data-bs-parent="#generalAccordion-<?= $counter_i ?>">
        <div class="accordion-body">
            <?= $acc_jiracontent ?>
        </div>
    </div>
</div>