<?php

declare(strict_types=1);

namespace Chassis\Helpers\Pcntl;

class PcntlSignals
{
    // If a process is being run from  terminal and that terminal suddenly goes
    // away then the process receives this signal. “HUP” is short for “hang up”
    // and refers to hanging up the telephone in the days of telephone modems.
    public const SIGHUP = 1;
    // The process was “interrupted”. This happens when you press Control+C on
    // the controlling terminal.
    public const SIGINT = 2;
    public const SIGQUIT = 3;
    // Illegal instruction. The program contained some machine code the CPU
    // can't understand.
    public const SIGILL = 4;
    // This signal is used mainly from within debuggers and program tracers.
    public const SIGTRAP = 5;
    // The program called the abort() function. This is an emergency stop.
    public const SIGABRT = 6;
    // An attempt was made to access memory incorrectly. This can be caused by
    // alignment errors in memory access etc.
    public const SIGBUS = 7;
    // A floating point exception happened in the program.
    public const SIGFPE = 8;
    // The process was explicitly killed by somebody wielding the kill
    // program.
    public const SIGKILL = 9;
    // Left for the programmers to do whatever they want.
    public const SIGUSR1 = 10;
    // An attempt was made to access memory not allocated to the process. This
    // is often caused by reading off the end of arrays etc.
    public const SIGSEGV = 11;
    // Left for the programmers to do whatever they want.
    public const SIGUSR2 = 12;
    // If a process is producing output that is being fed into another process that
    // consume it via a pipe (“producer | consumer”) and the consumer
    // dies then the producer is sent this signal.
    public const SIGPIPE = 13;
    // A process can request a “wake up call” from the operating system at some
    // time in the future by calling the alarm() function. When that time comes
    // round the wake up call consists of this signal.
    public const SIGALRM = 14;
    // The process was explicitly killed by somebody wielding the kill
    // program.
    public const SIGTERM = 15;
    // The process had previously created one or more child processes with the
    // fork() function. One or more of these processes has since died.
    public const SIGCHLD = 17;
    // (To be read in conjunction with SIGSTOP.)
    // If a process has been paused by sending it SIGSTOP then sending
    // SIGCONT to the process wakes it up again (“continues” it).
    public const SIGCONT = 18;
    // (To be read in conjunction with SIGCONT.)
    // If a process is sent SIGSTOP it is paused by the operating system. All its
    // state is preserved ready for it to be restarted (by SIGCONT) but it doesn't
    // get any more CPU cycles until then.
    public const SIGSTOP = 19;
    // Essentially the same as SIGSTOP. This is the signal sent when the user hits
    // Control+Z on the terminal. (SIGTSTP is short for “terminal stop”) The
    // only difference between SIGTSTP and SIGSTOP is that pausing is
    // only the default action for SIGTSTP but is the required action for
    // SIGSTOP. The process can opt to handle SIGTSTP differently but gets no
    // choice regarding SIGSTOP.
    public const SIGTSTP = 20;
    // The operating system sends this signal to a backgrounded process when it
    // tries to read input from its terminal. The typical response is to pause (as per
    // SIGSTOP and SIFTSTP) and wait for the SIGCONT that arrives when the
    // process is brought back to the foreground.
    public const SIGTTIN = 21;
    // The operating system sends this signal to a backgrounded process when it
    // tries to write output to its terminal. The typical response is as per
    // SIGTTIN.
    public const SIGTTOU = 22;
    // The operating system sends this signal to a process using a network
    // connection when “urgent” out of band data is sent to it.
    public const SIGURG = 23;
    // The operating system sends this signal to a process that has exceeded its
    // CPU limit. You can cancel any CPU limit with the shell command
    // “ulimit -t unlimited” prior to running make though it is more
    // likely that something has gone wrong if you reach the CPU limit in make.
    public const SIGXCPU = 24;
    // The operating system sends this signal to a process that has tried to create a
    // file above the file size limit. You can cancel any file size limit with the
    // shell command “ulimit -f unlimited” prior to running make though it is
    // more likely that something has gone wrong if you reach the file size limit
    // in make.
    public const SIGXFSZ = 25;
    // This is very similar to SIGALRM, but while SIGALRM is sent after a
    // certain amount of real time has passed, SIGVTALRM is sent after a certain
    // amount of time has been spent running the process.
    public const SIGVTALRM = 26;
    // This is also very similar to SIGALRM and SIGVTALRM, but while
    // SIGALRM is sent after a certain amount of real time has passed, SIGPROF
    // is sent after a certain amount of time has been spent running the process
    // and running system code on behalf of the process.
    public const SIGPROF = 27;
    // (Mostly unused these days.) A process used to be sent this signal when one
    // of its windows was resized.
    public const SIGWINCH = 28;
    // (Also known as SIGPOLL.) A process can arrange to have this signal sent
    // to it when there is some input ready for it to process or an output channel
    // has become ready for writing.
    public const SIGIO = 29;
    // A signal sent to processes by a power management service to indicate that
    // power has switched to a short term emergency power supply. The process
    // (especially long-running daemons) may care to shut down cleanlt before
    // the emergency power fails.
    public const SIGPWR = 30;
    // Unused
    public const SIGSYS = 31;

    public static array $toSignalName = [
        1 => "SIGHUP",
        2 => "SIGINT",
        3 => "SIGQUIT",
        4 => "SIGILL",
        5 => "SIGTRAP",
        6 => "SIGABRT",
        7 => "SIGBUS",
        8 => "SIGFPE",
        9 => "SIGKILL",
        10 => "SIGUSR1",
        11 => "SIGSEGV",
        12 => "SIGUSR2",
        13 => "SIGPIPE",
        14 => "SIGALRM",
        15 => "SIGTERM",
        17 => "SIGCHLD",
        18 => "SIGCONT",
        19 => "SIGSTOP",
        20 => "SIGTSTP",
        21 => "SIGTTIN",
        22 => "SIGTTOU",
        23 => "SIGURG",
        24 => "SIGXCPU",
        25 => "SIGXFSZ",
        26 => "SIGVTALRM",
        27 => "SIGPROF",
        28 => "SIGWINCH",
        29 => "SIGIO",
        30 => "SIGPWR",
        31 => "SIGSYS",
    ];
}
