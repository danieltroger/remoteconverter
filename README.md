Remoteconverter
===============

Usage: [php ]convert.php [-b]

If -b is specified and the function pcntl_fork is available and enabled we'll fork to background and write STDOUT and STDERR of this script and ffmpeg to encode.log in your systems temporary directory.

Configure the paramenters at the beginning to fit your needs.

This script does some checks, globs a filelist from the image directory and writes a concat list for ffmpeg to $TMPDIR/files.txt, then calls ffmpeg to encode the images and save to the specified outfile (and writes the output of ffmpeg live to the log or terminal), then loops through the list and deletes the raw files.

------------

Some thieves where in our flat and stole some shit,
 so I said we need a survailance cam. After some "research" i figured out they are <i>very</i> expensive, but found a <a href="http://www.codeproject.com/Articles/665518/Raspberry-Pi-as-low-cost-HD-surveillance-camera">guide</a> to do it with my raspberry pi with a software called <a href="http://www.lavrsen.dk/foswiki/bin/view/Motion/WebHome">motion</a> which records from the webcam when it detects motion.
 Then i tried to install the normal version on the pi (against the guide as always), but figured out that you really need this <a href="https://github.com/dozencrows/motion/tree/mmal-test">special build</a> that can use the raspi cam. So i bought a raspi cam and configured it.

 Now ages after optimizing I have it working with around 10 fps, but the GPU writes the movies to thousands of JPEG files, so this is a script that converts them to one movie using ffmpeg.


I am doing this from a remote computer because the pi is to slow (ofc).
