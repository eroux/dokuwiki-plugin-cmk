#
# Makefile to publish a Dokuwiki plugin 
# Copyright (C) 2013 Elie Roux <elie.roux@telecom-bretagne.eu>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
# --------------------------------------------------------------------
#


NAME = cmk
FILES = syntax.php README.md LICENSE plugin.info.txt
DIRS = 

all : tgz zip

zip: clean $(FILES) $(DIRS)
	@echo "Building zip file..."
	@mkdir -p $(NAME)
	@cp -R $(FILES) $(DIRS) $(NAME)
	@zip -rq $(NAME).zip --exclude \*~ -- $(NAME)

tgz: clean $(FILES) $(DIRS)
	@echo "Building tgz file..."
	@mkdir -p $(NAME)
	@cp -R $(FILES) $(DIRS) $(NAME)
	@tar -czf $(NAME).tgz --exclude=\*\*/\*~ -- $(NAME)

clean:
	@rm -rf $(NAME).tgz $(NAME).zip $(NAME)

