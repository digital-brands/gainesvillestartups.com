import os
import shutil
import subprocess
import sys

cwd = os.path.dirname(os.path.abspath(__file__))
style_css = os.path.abspath(os.path.join(cwd, "style.css"))

if sys.platform == 'win32':
    lessc = '"' + os.path.join(cwd, "../tdr-framework-core/lessc.cmd") + '"'
else:
    # Make sure 'lessc' is in PATH.
    lessc = os.popen("which lessc").read().strip() # trim the trailing \n
    if len(lessc) == 0:
        print("'lessc' compiler not found in PATH.")
        sys.exit()

# Expand paths and join them as a string.
command = ' '.join([
    lessc,
    '"' + os.path.abspath(os.path.join(cwd, "./less/gainesvillestartups.less")) + '"',
    '"' + style_css + '"'
])

print("Compiling: " + command)
if sys.platform == 'win32':
  subprocess.call(command)  # Because os.popen() is stupid on Windows.
else:
  os.popen(command).read()
print("Done.")

# Minify the CSS (if argument was given).
try:
  if sys.argv[1] == '-m':
    command = ' '.join([
      'java -jar',
      '"' + os.path.abspath(os.path.join(cwd, "./tools/yuicompressor.jar")) + '"',
      '"' + os.path.abspath(os.path.join(cwd, "style.css")) + '"',
      '-o ' + '"' + style_css + '"',
    ])
    print("Minifying: " + command)
    os.popen(command).read()

    # Copy the theme header docblock back to style.css
    print("Adding theme docblock...")
    theme_header_path = os.path.abspath(os.path.join(cwd, "./less/theme.less"))
    theme_header = open(theme_header_path, 'rb').read()
    style_contents = open(style_css, 'rb').read()

    open(style_css, 'wb+').write(theme_header + style_contents)

    print("Done.")
except IndexError:
  # Catch IndexError in case sys.argv[1] doesn't exist.
  pass

