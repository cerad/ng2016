<?php declare(strict_types=1);

namespace Zayso\Project;

/**
 * This class could probably go away
 * Move these flags directly into the project
 *
 * @property-read bool showHeaderImage
 * @property-read bool showSchedulesMenu
 * @property-read bool showResultsMenu
 * @property-read bool showFinalResults
 */
class ProjectShowFlags
{
    public $showHeaderImage;
    public $showSchedulesMenu;
    public $showResultsMenu;
    public $showFinalResults;

    public function __construct(
        bool $showHeaderImage,
        bool $showSchedulesMenu,
        bool $showResultsMenu,
        bool $showFinalResults
    ) {
        $this->showHeaderImage   = $showHeaderImage;
        $this->showSchedulesMenu = $showSchedulesMenu;
        $this->showResultsMenu   = $showResultsMenu;
        $this->showFinalResults  = $showFinalResults;
    }
}