<?php declare(strict_types=1);

namespace Zayso\Project;

/**
 * @property-read string projectId
 * @property-read string abbv
 * @property-read string title
 * @property-read string desc
 *
 * @property-read string regYear
 * @property-read string timeZone
 * @property-read string rainedOutKey
 *
 * @property-read ProjectContact support
 * @property-read ProjectContact refAdmin
 * @property-read ProjectContact refAssignor
 *
 * Virtual
 * @property-read AbstractPageTemplate    pageTemplate
 * @property-read AbstractContentTemplate welcomeTemplate
 *
 * @property-read bool showHeaderImage
 * @property-read bool showSchedulesMenu
 * @property-read bool showResultsMenu
 * @property-read bool showFinalResults
 */

interface ProjectInterface
{

}