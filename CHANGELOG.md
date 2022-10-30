# CHANGELOG

## [v1.4.3](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.4.3)

October 30th, 2022 - [v1.4.2...v1.4.3](https://github.com/zenstruck/schedule-bundle/compare/v1.4.2...v1.4.3)

* dd41eb6 [bug] `RunContext::$duration` can be negative under certain conditions (#71) by @gassan
* 13a8671 [minor] fixcs by @kbond
* e06431b [minor] remove branch alias by @kbond

## [v1.4.2](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.4.2)

September 30th, 2022 - [v1.4.1...v1.4.2](https://github.com/zenstruck/schedule-bundle/compare/v1.4.1...v1.4.2)

* 8af79f7 [bug] Change ping options to variablePrototype (#70) by @bpastukh

## [v1.4.1](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.4.1)

July 20th, 2022 - [v1.4.0...v1.4.1](https://github.com/zenstruck/schedule-bundle/compare/v1.4.0...v1.4.1)

* 99f8d9a [bug] Allow to add lazy commands by the inner command classname (#67) by @gisostallenberg, Giso Stallenberg <giso@unity-x.nl>

## [v1.4.0](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.4.0)

June 8th, 2022 - [v1.3.0...v1.4.0](https://github.com/zenstruck/schedule-bundle/compare/v1.3.0...v1.4.0)

* 191e113 [minor] support Symfony 6.1 (#63) by @kbond

## [v1.3.0](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.3.0)

April 14th, 2022 - [v1.2.1...v1.3.0](https://github.com/zenstruck/schedule-bundle/compare/v1.2.1...v1.3.0)

* 9c224b6 [minor] improve converting callbacks to strings (#62) by @kbond
* 0273756 [feature] add `AsScheduledTask` for self-schedule commands/services (#62) by @kbond
* 613bbec [minor] dep upgrade (#61) by @kbond
* 00b7d8f [minor] remove scrutinizer (#61) by @kbond
* 2f7cd2f [feature] add additional cron hash aliases (#61) by @kbond
* c910a93 [doc] Update define-schedule.md (#56) by @Lenny4
* 0dfcf62 [minor] add static code analysis with phpstan (#55) by @kbond
* e021707 [minor] run php-cs-fixer on lowest supported php version (#54) by @kbond

## [v1.2.1](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.2.1)

November 13th, 2021 - [v1.2.0...v1.2.1](https://github.com/zenstruck/schedule-bundle/compare/v1.2.0...v1.2.1)

* c04c992 [minor] add php 8.1 support by @kbond
* 33b432b [ci] use reusable actions (#53) by @kbond
* a0d76a8 [minor] add symfony 6 to ci matrix (#52) by @kbond

## [v1.2.0](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.2.0)

September 13th, 2021 - [v1.1.2...v1.2.0](https://github.com/zenstruck/schedule-bundle/compare/v1.1.2...v1.2.0)

* a65343c [minor] allow Symfony 6 (#51) by @kbond
* 7649b18 [minor][SMALL BC BREAK] schedule:run now has no output for no tasks (#49) by @kbond
* 49ade72 [minor] disable codecov pr annotations (#50) by @kbond
* 219878d [minor] add Symfony 5.3 to CI matrix (#46) by @kbond
* d4d9fe9 [minor] update php-cs-fixer to v3 by @kbond
* 87120a4 [minor] set deprectation fail threshold by @kbond

## [v1.1.2](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.1.2)

April 14th, 2021 - [v1.1.1...v1.1.2](https://github.com/zenstruck/schedule-bundle/compare/v1.1.1...v1.1.2)

* 07fbffd [minor] capture stdout output in failed process task result (#44) by @kbond
* 70a8160 [minor] lock php-cs-fixer version in ci (bug) by @kbond
* 90bfea4 [bug] in CommandTaskRunner, reset SHELL_VERBOSITY to pre-run state (#42) by @kbond

## [v1.1.1](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.1.1)

February 26th, 2021 - [v1.1.0...v1.1.1](https://github.com/zenstruck/schedule-bundle/compare/v1.1.0...v1.1.1)

* 8d0500f [minor] adjust deps to make it clear it isn't usable in Symfony 3.4 (#38) by @kbond
* 7d9cb91 [doc] Add missing namespace in the README's example (#35) by @justRau
* ae849c7 [minor] fix cs by @kbond
* 60bee6c [minor] replace removed phpunit method (#34) by @kbond
* e5e2e04 [minor] add codecov badge (#33) by @kbond
* 6fa2eea [minor] switch to codecov for code coverage (#32) by @kbond
* 4b3b027 [minor] further streamline gh actions (#31) by @kbond
* b775669 [minor] Streamline GitHub CI by using ramsey/composer-install (#30) by @kbond
* 40677dd [minor] add Symfony 5.2 to ci matrix by @kbond

## [v1.1.0](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.1.0)

November 15th, 2020 - [v1.0.1...v1.1.0](https://github.com/zenstruck/schedule-bundle/compare/v1.0.1...v1.1.0)

* ff3dcd2 [minor] support php8 (#25) by @kbond
* 68826f7 [minor] ci adjustments (#29) by @kbond
* a862b00 [minor] ci adjustments (#29) by @kbond
* 59fbaeb [feature][experimental] add MessageTask to schedule messenger messages (#28) by @kbond
* cd6bba4 [minor] cs fix (#27) by @kbond
* 2f788a6 [minor] Schedule::addPing()/CompoundTask::addPing() convenience methods (#27) by @kbond
* 6002b61 [minor] test on Symfony 5.1 (#26) by @kbond
* d54f71f [minor] switch flex branch to main (#26) by @kbond
* 6445da4 [doc] replace Envoyer with "Oh Dear" as example cron monitoring service by @kbond

## [v1.0.1](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.0.1)

September 27th, 2020 - [v1.0.0...v1.0.1](https://github.com/zenstruck/schedule-bundle/compare/v1.0.0...v1.0.1)

* f77865d [bug] Ensure kernel definition class is not null (#22) by @encreinformatique
* 4646d22 [bug] fix test checking for changed exception message (#23) by @kbond
* f7b3d21 [bug] remove minimum-stability from composer.json (#23) by @kbond
* f260289 [minor] self-update php-cs-fixer in action by @kbond
* 073385e [minor] adjust PingTaskRunnerTest tests (#20) by @kbond
* 7e47d8e [minor] add MockLogger to just check the message (not level) (#20) by @kbond
* 926e98f [doc] document "disable on deployment" strategy (fixes #10) (#17) by @kbond
* bc6bccd [doc] prefix "bin/console" with php (fixes #14) (#16) by @kbond
* 05a9c99 [doc] add additional readme badges by @kbond
* 0c06d89 [minor] switch to `Symfony\Component\Mime\Address::create()` (#11) by @kbond
* 21b252b [minor] adjust CI badge by @kbond
* a7cce5e [minor] fixcs by @kbond

## [v1.0.0](https://github.com/zenstruck/schedule-bundle/releases/tag/v1.0.0)

May 13th, 2020 - _[Initial Release](https://github.com/zenstruck/schedule-bundle/commits/v1.0.0)_
