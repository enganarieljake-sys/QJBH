<?php

namespace App\Support\Assistant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Runs an existing admin controller action as if the admin had submitted its
 * form, so a confirmed bot action gets exactly the same validation,
 * notifications, invitations, and room/bill status updates as the app's pages.
 */
class ControllerRunner
{
    private const FLASH_KEYS = ['success', 'warning', 'error'];

    /** Collects records created while an action runs, for Undo. */
    private static ?array $created = null;

    private const CREATED_EVENT = 'eloquent.created: *';

    /**
     * @param  array<string, Model>  $models  Route-bound models, by parameter name.
     * @return array{ok: bool, message: string, created: list<array{0: class-string, 1: int}>}
     */
    public static function run(User $admin, string $controller, string $method, array $models, array $input): array
    {
        // Nothing else in the app listens to this wildcard, so it is safe to add and remove.
        Event::listen(self::CREATED_EVENT, function (string $event, array $payload) {
            if (self::$created !== null && ($payload[0] ?? null) instanceof Model) {
                self::$created[] = [get_class($payload[0]), $payload[0]->getKey()];
            }
        });

        $request = Request::create('/admin/assistant/action', 'POST', $input);
        $request->setUserResolver(fn () => $admin);
        $request->setLaravelSession(app('session.store'));
        $request->headers->set('referer', route('admin.assistant.index'));

        // Form requests read route models (e.g. $this->route('tenant')) to skip the
        // record itself in "unique" checks, so give the request a matching route.
        $route = (new Route('POST', 'admin/assistant/action', []))->bind($request);
        foreach ($models as $parameter => $model) {
            $route->setParameter($parameter, $model);
        }
        $request->setRouteResolver(fn () => $route);

        $previous = app('request');
        app()->instance('request', $request);
        self::$created = [];

        try {
            app()->call([app($controller), $method], $models);
            $flash = self::pullFlash();

            if (isset($flash['error'])) {
                return ['ok' => false, 'message' => $flash['error'], 'created' => []];
            }

            return ['ok' => true, 'message' => $flash['success'] ?? $flash['warning'] ?? 'Done.', 'created' => self::$created];
        } catch (ValidationException $e) {
            return ['ok' => false, 'message' => implode(' ', array_merge(...array_values($e->errors()))), 'created' => []];
        } catch (HttpExceptionInterface $e) {
            return ['ok' => false, 'message' => $e->getMessage() ?: 'This can not be done right now.', 'created' => []];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'Something went wrong while saving. Please try it on the page itself.', 'created' => []];
        } finally {
            Event::forget(self::CREATED_EVENT);
            self::$created = null;
            self::pullFlash();
            app()->instance('request', $previous);
        }
    }

    /**
     * Controllers flash their result for the next page load; take it so it
     * doesn't also pop up later on whatever page the admin opens next.
     */
    private static function pullFlash(): array
    {
        $session = app('session.store');
        $flash = [];

        foreach (self::FLASH_KEYS as $key) {
            if ($session->has($key)) {
                $flash[$key] = $session->pull($key);
            }
        }

        $session->forget('errors');

        return $flash;
    }

    private static function listenForCreatedRecords(): void
    {
        if (self::$listening) {
            return;
        }

        self::$listening = true;
        Event::listen('eloquent.created: *', function (string $event, array $payload) {
            if (self::$created !== null && ($payload[0] ?? null) instanceof Model) {
                self::$created[] = [get_class($payload[0]), $payload[0]->getKey()];
            }
        });
    }
}
