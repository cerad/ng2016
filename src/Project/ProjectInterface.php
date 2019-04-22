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
 * TODO Move these into a lazy loaded contacts object
 * @property-read ProjectContact system
 * @property-read ProjectContact support
 * @property-read ProjectContact support2
 * @property-read ProjectContact refAdmin
 * @property-read ProjectContact refAssignor
 * @property-read ProjectContact gameScheduler
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