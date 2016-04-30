<?php

namespace Sminnee\Upgrader;

/**
 * Represents a set of code changes and warnings.
 * Generated by an Upgrader, to be displayed with a ChangeDisplay or written to disk with a CodeCollection.
 */
class CodeChangeSet
{
    private $fileChanges = [];

    private $warnings = [];

    private $affectedFiles = [];

    /**
     * Add a file change.
     */
    public function addFileChange($path, $contents)
    {
        if (isset($this->fileChanges[$path])) {
            user_error("Already added changes for $path, shouldn't add a 2nd time");
        }

        $this->fileChanges[$path] = $contents;

        if (!in_array($path, $this->affectedFiles)) {
            $this->affectedFiles[] = $path;
        }
    }

    /**
     * Add a warning about a given file.
     * Usually these warnings highlight upgrade activity that a developer will need to check for themselves
     */
    public function addWarning($path, $line, $warning)
    {
        if (!isset($this->warnings[$path])) {
            $this->warnings[$path] = [];
        }

        $this->warnings[$path][] = "Line $line: $warning";

        if (!in_array($path, $this->affectedFiles)) {
            $this->affectedFiles[] = $path;
        }
    }

    /**
     * Return all the file changes, as a map of path => contents
     * @return array
     */
    public function allChanges()
    {
        return $this->fileChanges;
    }

    /**
     * Return all affected files, in the order that they were added to the CodeChangeSet
     * @return array
     */
    public function affectedFiles()
    {
        return $this->affectedFiles;
    }

    /**
     * Returns true if the given path has been altered in this change set
     * @param string $path
     * @return boolean
     */
    public function hasNewContents($path)
    {
        return isset($this->fileChanges[$path]);
    }

    /**
     * Returns true if the given path has warnings in this change set
     * @param string $path
     * @return boolean
     */
    public function hasWarnings($path)
    {
        return isset($this->warnings[$path]);
    }

    /**
     * Return the file contents for a given path
     * @param string $path
     * @return string
     */
    public function newContents($path)
    {
        if (isset($this->fileChanges[$path])) {
            return $this->fileChanges[$path];
        } else {
            throw new \InvalidArgumentException("No file changes found for $path");
        }

    }

    /**
     * Return the warnings for a given path
     * @param string $path
     * @return array
     */
    public function warningsForPath($path)
    {
        if (isset($this->warnings[$path])) {
            return $this->warnings[$path];
        } else {
            throw new \InvalidArgumentException("No warnings found for $path");
        }
    }
}
