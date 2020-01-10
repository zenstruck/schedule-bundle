# The ScheduleBundle

[![Test Status](https://github.com/kbond/schedule-bundle/workflows/Tests/badge.svg)](https://github.com/kbond/schedule-bundle/actions?query=workflow%3ATests)
[![Standards](https://github.com/kbond/schedule-bundle/workflows/Standards/badge.svg)](https://github.com/kbond/schedule-bundle/actions?query=workflow%3AStandards)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kbond/schedule-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kbond/schedule-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/kbond/schedule-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kbond/schedule-bundle/?branch=master)

Schedule Cron jobs (Symfony commands/callbacks/bash scripts) within your Symfony
application. Most applications have jobs that need to run at specific intervals.
This bundle enables you to define these jobs in your code. Job definitions (tasks)
are version controlled like any other feature of your application. A single Cron
entry (`schedule:run` command) on your server running every minute executes due
tasks.

The inspiration and some of the API/code for this Bundle comes from [Laravel's
Task Scheduling feature](https://laravel.com/docs/master/scheduling).

[Read the documentation](doc/index.md)
