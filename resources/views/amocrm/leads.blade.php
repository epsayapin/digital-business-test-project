<h1>Leads</h1>


@foreach($leads as $lead)
    <ul>
        <li>
            <b>Название сделки</b>
            {{ $lead->getName() ?? "Имя не указано" }}
        </li>

        <li>
            <b>Ответственный</b>
            @if($lead->getResponsibleUserId())
                `{{ $users->getBy("id", $lead->getResponsibleUserId())->getName() }}
            @else
                Пользователь не указан
            @endif
        </li>


        @if($lead->getContacts())
            <b>Имя контакта</b>
            @foreach($lead->getContacts() as $contact)
                <li>
                    Имя -
                    {{ $contacts->getBy("id", $contact->getId())->getName() }}

                    @if ($contacts->getBy("id", $contact->getId())->getCustomFieldsValues())

                        Телефон -
                        {{
                            $contacts->getBy("id", $contact->getId())
                            ->getCustomFieldsValues()->getBy("fieldCode", "PHONE")->getValues()[0]->value

                            }}
                    @else
                        Телефон не указан
                    @endif

                    @if ($contacts->getBy("id", $contact->getId())->getCustomFieldsValues())

                        Почта -
                        {{
                            $contacts->getBy("id", $contact->getId())->getCustomFieldsValues()->getBy("fieldCode", "EMAIL")->getValues()[0]->value
                            }}
                    @else
                        Почта не указана
                    @endif
                </li>

            @endforeach
        @else
            <li>
                Нет контактов
            </li>
        @endif

        <li>
        <b>Название комании</b>
        {{ $lead->getCompany() ? $companies->getBy("id", $lead->getCompany()->getId())->getName() : "Имя комании не указано"  }}

        </li>
    </ul>
@endforeach


Отправить тестовую заявку
<form method="POST" action="{{ route("amocrm.send-test-lead")  }}">
    @csrf

    <input type="submit" value="Отправить">
</form>