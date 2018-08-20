## Per-branch pre-build hook scripts of the contentpool project

Files named the same as the current branch will be executed prior to building
the project via "phapp build". This can be used to customize versions/vendors
used for a certain branch.

Slashes in branch names are replaced by "--", e.g. for "feature/test" a script 
"feature--test.sh" is executed.

Note: The file must be named "${BRANCH}.sh" *and* must be executable.

Custom versions of the contentpool server can be used by setting the
variable LAUNCH_SATELLITE_GIT_BRANCH. 
