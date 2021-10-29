# Creating a release
1. Run the `release.php -v=x.x.x` with the new version number
2. Rever the changes in the `manifest.xml` that are not related to the release   
2. Run the `build.sh x.x.x` with the new version number
3. Copy the SHA384 from the `checksums.json` file to the `manifest.xml`
4. Upload the generated files to the Github release
