# EDT

## Releasing

This repository uses [symplify/monorepo-builder](https://github.com/symplify/monorepo-builder) to
release several packages.

To create a new release, `bin/monorepo-builder release <major|minor|patch>` can be used after all changes have been done.

### Changelog management

The monorepo builder release flow kinda expects a `## Unreleased` headline to be at the top of the Changelog
but does not currently create a new one after a release. So please remember to manually add it after releases.

### Caveats

**Patch**-Releases don't work all-too-well fully automatically at the moment. Therefore please 
use **Minor**-Releases for the time being.

### Credits and acknowledgements

Conception and implementation by Christian Dressler with many thanks to [eFrane](https://github.com/eFrane).
