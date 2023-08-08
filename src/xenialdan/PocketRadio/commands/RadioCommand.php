<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\customui\elements\Button;
use xenialdan\customui\elements\Dropdown;
use xenialdan\customui\elements\Slider;
use xenialdan\customui\windows\CustomForm;
use xenialdan\customui\windows\SimpleForm;
use xenialdan\libnbs\Song;
use xenialdan\PocketRadio\Loader;

class RadioCommand extends CommandExecutor
{
    public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool
    {
        $return = true;
        try {
            switch ($args[0] ?? "menu") {
                case "menu":
                {
                    $title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Change your volume";
                    $form = new SimpleForm($title);
                    $form->addButton(new Button("Next"));
                    $form->addButton(new Button("Pause"));
                    $form->addButton(new Button("Previous"));
                    $form->addButton(new Button("Volume"));
                    $form->addButton(new Button("Select song"));
                    $form->setCallable(function (Player $player, $data) {
                        switch ($data) {
                            case "Next":
                            {
                                Loader::playNext();
                                break;
                            }
                            case "Pause":
                            {
                                Loader::getInstance()->getScheduler()->cancelAllTasks();
                                break;
                            }
                            case "Previous":
                            {
                                $player->sendMessage("Under todo, can not play previous yet");
                                break;
                            }
                            case "Volume":
                            {
                                $player->getServer()->dispatchCommand($player, "radio volume");
                                break;
                            }
                            case "Select song":
                            {
                                $player->getServer()->dispatchCommand($player, "radio select");
                                break;
                            }
                        }
                    });
                    $sender->sendForm($form);
                    break;
                }
                case "volume":
                {
                    $volume = Loader::getVolume($sender);
                    if ($volume === false) {
                        $sender->sendMessage(TextFormat::RED . "Error accessing volume config");
                        $return = false;
                        break;
                    }
                    $title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Change your volume";
                    $form = new CustomForm($title);
                    try {
                        $slider = new Slider("Volume", 0, 100, 10.0);
                        $slider->setDefaultValue($volume);
                        $form->addElement($slider);
                    } catch (Exception $e) {
                    }
                    $form->setCallable(function (Player $player, $data) {
                        Loader::setVolume($player, (int)$data[0]);
                    });
                    $sender->sendForm($form);
                    break;
                }
                case "select":
                {
                    $title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Select a song";
                    $form = new CustomForm($title);
                    $dropdown = new Dropdown("Song", []);
                    /** @var Song[] $d */
                    $d = [];
                    foreach (Loader::$songlist as $i => $song) {
                        $songName = basename($song->getPath(), ".nbs");
                        $d[$songName] = $song;
                        $dropdown->addOption($songName, $i === 0);
                    }
                    $form->addElement($dropdown);
                    $form->setCallable(function (Player $player, $data) use ($form, $d) {
                        if (empty($data[0]) || $data[0] === "") {
                            $player->sendForm($form);
                            return;
                        }
                        Loader::playNext($d[$data[0]] ?? null);
                    });
                    $sender->sendForm($form);
                    break;
                }
                default:
                {
                    throw new \InvalidArgumentException("Unknown argument supplied: " . $args[0]);
                }
            }
        } catch (\Error $error) {
            $this->getPlugin()->getLogger()->error($error->getMessage());
            $this->getPlugin()->getLogger()->error($error->getLine());
            $return = false;
        } finally {
            return $return;
        }
    }
}
