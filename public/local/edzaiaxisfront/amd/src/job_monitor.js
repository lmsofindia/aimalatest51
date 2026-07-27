// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Job monitor — polls job status for a set of CMs and fires callbacks.
 *
 * Usage:
 *   import * as JobMonitor from 'local_edzaiaxisfront/job_monitor';
 *   JobMonitor.start(jobs, {onUpdate, onAllDone});
 *
 * @module     local_edzaiaxisfront/job_monitor
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from 'local_edzaiaxisfront/repository';

const POLL_INTERVAL_MS = 3000; // 3 seconds

let intervalId = null;
let jobMap = {}; // cmid → job state
let callbacks = {};

/**
 * Start monitoring a list of jobs.
 *
 * @param {Array}  jobs         [{cmid, job_id, status, progress}]
 * @param {Object} cbs          {onUpdate(cmid, status, progress), onAllDone()}
 */
export const start = (jobs, cbs) => {
  stop(); // Clear any existing poller

  callbacks = cbs || {};
  jobMap = {};

  jobs.forEach((j) => {
    jobMap[j.cmid] = { ...j };
  });

  poll(); // Immediate first check
  intervalId = setInterval(poll, POLL_INTERVAL_MS);
};

/**
 * Stop the poller (call when navigating away or all jobs done).
 */
export const stop = () => {
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }
};

const poll = async () => {
  const pending = Object.values(jobMap).filter((j) => !isTerminal(j.status));

  if (!pending.length) {
    stop();
    if (typeof callbacks.onAllDone === "function") {
      callbacks.onAllDone();
    }
    return;
  }

  await Promise.allSettled(pending.map(checkJob));
};

const checkJob = async (job) => {
  try {
    const result = await Repository.getJobStatus(job.cmid);
    const prev = jobMap[job.cmid];

    jobMap[job.cmid] = {
      ...prev,
      status: result.status,
      progress: result.progress,
    };

    if (result.status !== prev.status || result.progress !== prev.progress) {
      if (typeof callbacks.onUpdate === "function") {
        callbacks.onUpdate(
          job.cmid,
          result.status,
          result.progress,
        );
      }
    }
  } catch (_) {
    // Network hiccup — keep polling
  }
};

const isTerminal = (status) =>
  ["completed", "ready", "failed"].includes(status);
