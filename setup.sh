#/bin/sh

ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew tap caskroom/cask
brew install caskroom/cask/brew-cask

brew install nkf
brew install tree
brew install git 
brew install svn
brew install php70


brew cask install coteditor
brew cask install alfred
brew cask install skype
brew cask install google-japanese-ime
brew cask install google-chrome
brew cask install phpstorm
brew cask install postman
brew cask install sequel-pro
brew cask install sourcetree

#font
brew install Caskroom/cask/xquartz
brew install autoconf
brew install automake
brew install pkg-config

brew tap sanemat/font
brew install sanemat/font/ricty
cp -f /usr/local/Cellar/ricty/4.0.1/share/fonts/Ricty*.ttf ~/Library/Fonts/
fc-cache -vf



