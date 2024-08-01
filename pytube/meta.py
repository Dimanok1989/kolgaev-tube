import sys
from pytubefix import YouTube
from pytubefix.cli import on_progress

yt = YouTube(sys.argv[1], on_progress_callback = on_progress)

print(yt.title)
print(yt.thumbnail_url)
print(yt.streams)
