<?php

namespace App\Module\LobbyModule\Controller;

use App\Controller\AbstractController;
use App\Exception\JSONException;
use App\Http\JSONResponse;
use App\Module\CourseSheetModule\Model\CourseSheetModel;

class LobbyController extends AbstractController
{

    public function run(): void
    {
        switch ($this->getRequest()->getAction()) {
            case 'coursesheets' || 'messages' || 'lobby' || 'update' || 'newCourseSheet':
                $this->checkToken();
                $idLobby = (int)$this->getRequest()->getParam();
                $result = [];
                $rightsOnLobby = $this->getModel()->checkRights($idLobby, $this->getRequest()->getToken());
                if ('none' !== $rightsOnLobby) {
                    if ('admin' === $rightsOnLobby || 'user' === $rightsOnLobby) {
                        switch ($this->getRequest()->getAction()) {
                            case 'coursesheets':
                                $result = $this->getModel()->getCourseSheets($idLobby);
                                break;

                            case 'messages':
                                $result = $this->getModel()->getMessages($idLobby);
                                break;

                            case 'consult':
                                $result = $this->getModel()->getLobbyById($idLobby);
                                break;
                        }
                    }
                    if ('admin' === $rightsOnLobby) {
                        switch ($this->getRequest()->getAction()) {
                            case 'newCourseSheet':
                                $result = (new CourseSheetModel($this->getModel()->getConnection()))->addCourseSheet(
                                    $idLobby,
                                    $this->getRequest()->getTitle(),
                                    $this->getRequest()->getFile()['name'],
                                    $this->getRequest()->getFile()['tmp_name'],
                                    $this->getRequest()->getDescription(),
                                    );
                                break;

                            case 'deleteCourseSheet':
                                $result = (new CourseSheetModel($this->getModel()->getConnection()))->deleteCourseSheet($idLobby, (int)$this->getRequest()->getId());
                                break;

                            case 'addUser':
                                $result = $this->getModel()->addUser($idLobby, $this->getRequest()->getEmail());
                                break;

                            case 'removeUser':
                                $result = $this->getModel()->removeUser($idLobby, $this->getRequest()->getEmail());
                                break;

                            case 'addWriteRight':
                                $result = $this->getModel()->addWriteRight($idLobby, $this->getRequest()->getEmail());
                                break;

                            case 'removeWriteRight':
                                $result = $this->getModel()->removeWriteRight($idLobby, $this->getRequest()->getEmail());
                                break;

                            case 'makePrivate':
                                $result = $this->getModel()->makePrivate($idLobby);
                                break;

                            case 'makePublic':
                                $result = $this->getModel()->makePublic($idLobby);
                                break;

                            case 'update':
                                if (isset($this->getRequest()->label)) {
                                    $result['message_label'] = $this->getModel()->updateLobby($idLobby, ['label_lobby' => $this->getRequest()->getLabel()])['message'];
                                }
                                if (isset($this->getRequest()->description)) {
                                    $result['message_description'] = $this->getModel()->updateLobby($idLobby, ['description' => $this->getRequest()->getDescription()])['message'];
                                }
                                if (isset($this->getRequest()->file)) {
                                    $result['message_logo'] = $this->getModel()->updateLogo(
                                        $idLobby,
                                        $this->getRequest()->getFile()['name'],
                                        $this->getRequest()->getFile()['tmp_name'],
                                        )['message'];
                                }
                                break;
                        }
                    }
                } else {
                    new JSONException('You don\'t have the right to access this lobby');
                }
                new JSONResponse($result);
                break;


            default:
                new JSONException($this->getRequest()->getAction() . ' action doesn\'t exists');
                break;
        }
    }
}
