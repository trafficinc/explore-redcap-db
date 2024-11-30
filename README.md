# Tool to Explore the RedCap Database

This is a small tool to get to know which tables in Redcap are affected by actions via DB inserts and deletes.

Update  `dbconfig.example.php` with database creds, then change filename to `dbconfig.php`

First run `php learndb.php get-tables` to update the tables for this tool to use.

1) Before you make a change in Redcap run: $ `php learndb.php before`

2) Do something on the app UI, like fill out and submit a form, ie. create a new project.

3) After you make a change in Redcap run: $ `php learndb.php after`

4) To see what was changed in the DB tables, run: $ `php learndb.php compare`

Now you will be able to see what database tables were effected! **Note: this works on inserts and deletes, not updates.  Updates are more complicated and not supported yet, if you have a good idea, please share and we can implement it for updates.

## Example output:

Differences found:

&nbsp;&nbsp;Difference at table 'redcap_crons_history' : 9908 vs 9921
  
&nbsp;&nbsp;Difference at table 'redcap_events_arms' : 16 vs 17
  
Deletes found:

&nbsp;&nbsp;table 'redcap_crons_sample' [DELETE] : OLD: 9609 vs. NEW: 9501

Inserts found:

&nbsp;&nbsp;table 'redcap_crons_history' [INSERT] : OLD: 9908 vs. NEW: 9921
  
&nbsp;&nbsp;table 'redcap_events_arms' [INSERT] : OLD: 16 vs. NEW: 17

### Other

Save output to a file: `php learndb.php compare > new_project_output.txt`
