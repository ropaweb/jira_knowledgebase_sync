<?php
use Ropaweb\JiraKnowledgebaseSync\Entry;

/** @var int $counter_i */
$counter_i = $this->getVar('counter_i');

/** @var array $categories */
$categories = $this->getVar('categories');

/** @var string $project_code */
$project_code = $this->getVar('project_code');

$count_categories = count($categories);
$j = 1;

    if ($count_categories > 1):
    // Überprüfen, ob Kategorien mehr als 1 sind
    ?>
    <div class="accordion col-12 col-lg-10" id="mainAccordion">
    <?php endif ?>

        <?php

        foreach ($categories as $category) {
            $entry = Entry::query()->where('jira_knowledgebase_sync_category_id', $category->getId())->where('jiraproject', $project_code)->where('status', 1)->orderBy('name', 'ASC')->find();
            // dump($entry);
        ?>

        <?php if ($count_categories > 1): // Überprüfen, ob Kategorien mehr als 1 sind?>
            <div class="accordion-item">
            <?php
                //  Accordion Header
                $this->setVar('category_name', $category->getName(), false);
                echo $this->subfragment('acc_first_header.php');

            ?>

            <div id="collapseGeneral-<?= $counter_i ?>" class="accordion-collapse main-accordion collapse" aria-labelledby="headingGeneral"
                <div id="collapseGeneral-<?= $counter_i ?>"
                class="accordion-collapse collapse"
                aria-labelledby="heading-General-<?= $counter_i ?>"
                data-bs-parent="#mainAccordion">
                <div class="accordion-body text-start">
            <?php else: ?>
                <h2><?= $category->getName() ?></h2>
            <?php endif ?>
                    <div class="accordion" id="generalAccordion-<?= $counter_i ?>">

                        <?php
                        foreach ($entry as $acc) {
                            // Zweite Accordion Ebene
                            $this->setVar('counter_j', $j, false);
                            $this->setVar('acc_name', $acc->name, false);
                            $this->setVar('acc_jiracontent', $acc->jiracontent, false);

                            echo $this->subfragment('acc_second.php');

                            ++$j;
                        } // end of foreach
                        ?>

                    </div> <!-- end of generalAccordion !-->
            <?php if ($count_categories > 1): // Überprüfen, ob Kategorien mehr als 1 sind?>
                </div> <!-- end of accordion-body !-->
            </div>
        </div>
        <?php endif ?>

        <?php
        // Parameter, die alle folgenden Eintraege
        // $aria_first_item = "false";
        // $collapsed_items = "collapsed";
        $show_content = '';

        ++$counter_i;
        } // end of foreach
        ?>

    <?php if ($count_categories > 1): ?>
    </div> <!-- end of accordion main !-->
    <?php endif ?>