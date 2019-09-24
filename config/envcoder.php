<?php

return [
    /*
     * Determines how to resolve any differences in the encrypted
     * .env file. Options are:
     *
     * 'merge' => Will merge changes in both files, and overwrite duplicates with what is in .env.enc (default)
     * 'prompt' => Will prompt you for each value that has a different value in .env.enc vs .env or is not in both files
     * 'overwrite' => Will completely overwrite your .env with what is in the encrypted version
     * 'ignore' => Will ignore any changes in your encrypted .env (ie. will not decrypt)
     */
    'resolve' => 'merge',
];
