<?php

namespace Sijot\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Sijot\Http\Requests\BanValidator;
use Sijot\Http\Requests\Usersvalidator;
use Sijot\Mail\BlockEmailNotification;
use Sijot\Mail\UserCreationMail;
use Sijot\Notifications\BlockNotification;
use Sijot\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Sijot\Role;
use Sijot\Permission;

/**
 * Class UsersController
 * @package Sijot\Http\Controllers
 */
class UsersController extends Controller
{
    /**
     * Variable for the user model. 
     * 
     * @var User
     */
    private $userDB;

    /**
     * Variable for the permissions model. 
     * 
     * @var Permission
     */
    private $permissions;

    /**
     * The variable for the roles model. 
     * 
     * @var Role
     */
    private $roles;

    /**
     * UsersController constructor.
     *
     * @param User       $userDB      The user model for the database.
     * @param Role       $roles       The Roles database model.
     * @param Permission $permissions The Permissions database model.
     * 
     * @return void
     */
    public function __construct(Role $roles, Permission $permissions, User $userDB)
    {
        $this->middleware('auth');
        $this->middleware('forbid-banned-user');

        $this->userDB      = $userDB;
        $this->roles       = $roles;
        $this->permissions = $permissions;
    }

    /**
     * Get the usermanagement backend view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $data['title']          = 'Gebruikers beheer';
        $data['users']          = $this->userDB->paginate(25);
        $data['roles']          = $this->roles->paginate(25);
        $data['permissions']    = $this->permissions->paginate(25);

        return view('users.index', $data);
    }

    /**
     * Get the user id and name and return it in json.
     *
     * @param int $userId The id for the user in the database.
     * 
     * @return mixed
     */
    public function getById($userId)
    {
        try { //* Try to find and output the record.
            //? Shouldn't this be not provide in some API form?  
            return json_encode($this->userDB->select(['id', 'name'])->findOrFail($userId));
        } catch (ModelNotFoundException $notFoundException) { //* The user is not found.
            return app()->abort(404);
        }
    }

    /**
     * Ban a user in the system.
     *
     * @param BanValidator $input The user input validator.
     * 
     * @return mixed
     */
    public function block(BanValidator $input)
    {
        if ((int) $input->id === auth()->user()->id) {
            flash('Je kan jezelf niet blokkeren.')->error();
            return back(302);
        }

        try { //* To ban the user.
            $user = $this->userDB->findOrFail($input->id);
            $user->ban(['comment' => $input->reason, 'expired_at' => Carbon::parse($input->eind_datum)]);

            // $notifyUsers = $this->userDB->role('Admin')->get();
            $notifyUsers = $this->userDB->all();

            // Notifications.
            Notification::send($notifyUsers, new BlockNotification($notifyUsers));
            Mail::to($user)->send(new BlockEmailNotification($user));

            flash($user->name . 'Is geblokkeerd tot ' . $input->eind_datum)->success();

            return back(302);
        } catch (ModelNotFoundException $modelNotFoundException) { //* Could not ban the user.
            return app()->abort(404);
        }
    }

    /**
     * Unblock some user in the system
     *
     * @param integer $userId The id for the user in the database. 
     * 
     * @return mixed
     */
    public function unblock($userId) 
    {
        try { // To find the user in the database. 
            $user = $this->userDB->findOrFail($userId);

            if ($user->isBanned()) { // The user is banned.
                $user->unban(); // Unban the user in the system
                flash('De gebruiker is terug geactiveerd');
            } else { // The user is not banned
                flash('Wij konden de gebruiker niet activeren.')->error();
            }

            return back(302);
        } catch (ModelNotFoundException $modelNotFoundException) { //* The user is not found in the system.
            return app()->abort(404); // Could not find the user in the database. 
        }
    }

    /**
     * Edit view for the user permissions.
     *
     * @return mixed
     */
    public function editPermission($userId)
    {
        try { // To ifind the user in the system.
            $data['user']        = $this->userDB->findOrFail($userId);
            $data['permissions'] = $this->permissions->pluck('name', 'id'); //? Results in the view: <option value='<id>'> <name> </option>
            $data['roles']       = $this->roles->pluck('name', 'id');       //? Results in the view: <option value='<id>'> <name> </option>

            return view('acl.edit-user-permissions', $data);
        } catch (ModelNotFoundException $exception) { // The user isn't found in the system.
            flash("Wij konden de gebruiker niet vinden in het systeem");
        }

        return redirect()->route('users.index');
    }

    /**
     * Store the new permissions for the given user.
     *
     * @param  Request $input The user given input.
     * @return mixed
     */
    public function storePermission(Request $input, $userId)
    {
        try { //? To find and update the user.
            $data['user']        = $this->userDB->findOrFail($userId);
            $data['roles']       = $input->get('roles', []); 
            $data['permissions'] = $input->get('permissions', []); 

            if ($data['user']->roles()->sync($data['roles']) && $data['user']->permissions()->sync($data['permissions'])) {
                flash("De rechten en permissions van {$data['user']->name} zijn aangepast.")->success();
            }

            return redirect()->route('users.index');
        } catch (ModelNotFoundException $exception) { //? user not found.
            flash('Wij konden de rechten en permissions niet aanpassen.')->error();
        } 
    }

    /**
     * Create a new login in the database.
     *
     * @param Usersvalidator $input The user input validation.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UsersValidator $input)
    {
        // TODO: Refactor the data variables. Instead use the $input variable.

        $data['name']     = $input->name; 
        $data['email']    = $input->email;
        $data['password'] = bcrypt($input->password);

        if ($user = $this->userDB->create($data)) { // Try to create the user.
            // Send info mail.

            // TODO: Set the mail in test files. Mailable errors phpunit.
            Mail::to($user->email)->send(new UserCreationMail($input->all()));


            // Set flash message.
            flash('De login is aangemaakt.');
        }

        return back(302);
    }

    /**
     * Delete a user in the database.
     *
     * @param integer $userId The id in the database for the user. 
     * 
     * @return mixed
     */
    public function delete($userId) 
    {
        try { // To find the user in the database. 
            $user = $this->userDB->findOrfail($userId); 

            if ($user->delete()) { // try to delete the user.
                flash( "{$user->name} Is verwijderd uit het systeem.");
            }

            return back(302);
        } catch(ModelNotFoundException $modelNotFoundException) { //* The user is not found in the system.
            return app()->abort(404); // The given user is not found.
        }  
    }
}
