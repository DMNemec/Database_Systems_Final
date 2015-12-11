 The Final Project
Due Date: Beginning of the final exam period, Tuesday, Dec. 15, 8:00-9:50 a.m.

As has been intimated all term, your final project will involve interaction with a single hlstats database for the purpose of scoring a mock pick-up game tournament.
General Details

For the final project...

    You will be required to upload log files from Steam game matches to your upload directories on storm.
    You will then parse these log files for the purpose of:
        Determining players involved
            Adding new players if they do not yet exist in the database
            Modifying requisite data if it does not yet exist in the database
            Aggregating player game data 
        Determining game properties such as
            kills
            deaths
            End-of-game notoriety
            Team Wins/Losses 
    Your analysis of the game file (log) will contribute along with others' to the global hlstats database. 

More Specific Details

Your actions on each log file:

    Must be idempotent. That is to say, parsing a log file more than once is equivalent to parsing it just one time. You should be able to parse the log files already processed by other students without effecting the data in the database. The affect of parsing an unparsed log file will cause changes to the database, but a subsequent re-parsing of the log file by you or another student must result in no data alteration.
    Modify the global hlxce database on storm.cs.uni.edu.
    Affect the global virtualized view (to be created Nov 10) hlstats_Tournament_Status indirectly through your modifications to the materialized tables. 

Fine Details

Your project is to provide the end user (me) a form for uploading game log files. You are to restrict uploads to only files ending in "log" and are to reject any other file uploads. After processing a log file, you are to delete the uploaded file. Files may be uploaded multiple times.

For each log file you are to parse all required data from the log file so as to be able to calculate a player's standing over the last 2 days, across all game modes, across all game servers, according to the formula:

                                          PlayerNotoriety - GameAvgNotoriety
           max(# Kills - 3 * TeamKills + -------------------------------------, 0)
                                                    GameAvgNotoriety
    ---------------------------------------------------------------------------------------
  max(max(# Deaths, 1) - 2 * #(Times on winning team if a teamplay mode) - #(Games played),1)


Don't let the formula intimidate you. The formula will be rolled into the view. Your responsibility will be to manipulate tables based on the parsing of the logs and add to the hlstats_Tournament_Data which will affect the view.

(PlayerNotoriety and GameAvgNotoriety are cumulative values.) A player's game stats ONLY count toward the tournament formula if the player was in-game for 10 minutes or longer. This is a cumulative quantity, as it is possible that players may leave and then rejoin. As long as their cumulative in-game time is 10 minutes or more and the player remains in-game at the conclusion of the match, their stats will apply toward the tournament calculation.

Player's game stats ONLY count toward the tournament formula if there were four or more (real) players participating in the game, each for 10 minutes or longer.

Upon meeting the above criteria, your processing of the log file is required to update all required tables of the hlxce database when constraints require it, such as hlstats_Players, hlstats_Servers, and so forth. The data that you enter into any table must be consistent with the existing data in the table. All SteamID formats introduced inserted into any table must be SteamID3 format.

Your specific parsing of the datafile is to (possibly) modify the newly-introduced hlstats_Tournament_Data table, with the schema ('playerId','matchId','matchDate','serverId','PlayerNotoriety','GameAvgNotoriety','Kills','TeamKills','Deaths','ValidGame','WinningTeam').

    playerId is a Foreign Key constraint that references hlstats_Players.playerId.
    matchDate is the time at the end of the match, marked by the published Notoriety summation.
    WinningTeam is a boolean field. It is true if the player finishes the match on the winning team or if the match is a "Free-for-all" match (everyone against everyone else). Equivalently, this value is false only if the player participated in a multi-team game mode and did not finish the match on the winning team.
    GameAvgNotoriety is the average notoriety of all real players across all teams at the end of the match according to the end-of-game notoriety summary.
    ValidGame reflects the criteria outlined above:
        The player must have been in-game for at least 10 minutes.
        There must have been four players in the match competing for at least 10 minutes each.
        The player must have finished the match in-game.
            Note that there could be situations where the match ends with less than four players, but the game is still considered valid. For example, if there were four players that were in-game, each for at least 10 minutes during the course of the match, then the match would still be considered a valid match. 
    TeamKills reflects the number of times a player kills a teammate in a match with friendly fire enabled. 

In the event that the database becomes irrecoverably corrupt, you must notify me immediately and I will reset the data. If your processing of data leaves the database or tables in an inconsistent state so as to prevent the progress of other students, and you do not notify me within 12 hours, your score shall be reduced by 5 points per day for each day the database was rendered unusable.
Evaluation

The following will be used to evaluate your project:

    Handling a portion of the collective pool of log files.
        Testing will involve taking the entire collection of log files and distributing them randomly across all students in the class. Each student possesses unique files to process. Your individual contributions to the database tables will be compared to the correct database modifications. 
    Handling all of the log files.
        Testing will involve resetting the database and uploading all log files to your project submission page. Your results will be compared to the correct global results. 
    Idempotent checks.
        Testing will involve submission of possibly redundant data and possibly incomplete log files. Your results will be check to verify no modifications to the database occur. 
    Code analysis
        Your code will be examined for adequate comments [all of your logic must be commented - each loop, each block, each function, each exception].
        Your code will be examined for uniqueness. All codes will be subject to plagiarism scrutiny and subject to code similarity analysis tools. Copying, or permitting your code to be copied, is a violation of the University Code of Ethical Conduct, and will not be tolerated. Penalties for those found sharing or copying code will be according to the University Student Academic Ethics Policy. 

Test Logs

Use the log files found here for testing your code.
Recommendations

    Test your scripts on your local database instead of the global database first.
    Start early and go slow. Much better than starting late and needing to code fast.
    You will need to look into PCREs for the parsing component of this task.
    Use mysqli or PDO php calls for communication with the database. 