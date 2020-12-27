<h2>Отправить тестовую заявку</h2>
<form method="POST" action="{{ route("amocrm.send-test-lead")  }}">
    @csrf
    <input type="submit" value="Отправить">
</form>
<h2>Leads</h2>

@if($leads)
    @forelse($leads as $lead)
        <ul>
            <li>
                <b>Название сделки</b>
                {{ $lead->getName() ?? "Не указано" }}
            </li>
            <li>
                <b>Ответственный</b>
                @if($lead->getResponsibleUserId())
                    `{{ $users->getBy("id", $lead->getResponsibleUserId())->getName() }}
                @else
                    Отвественный пользователь не указан
                @endif
            </li>

            @if($lead->getContacts())
                <li><b>Контакты</b></li>
                @foreach($lead->getContacts() as $contact)
                    <li>

                        <i>Имя - </i>
                        @if($contacts->getBy("id", $contact->getId())->getName())
                            {{ $contacts->getBy("id", $contact->getId())->getName() }}
                        @else
                            Не указано
                        @endif
                        @if ($contacts->getBy("id", $contact->getId())->getCustomFieldsValues()
                        && $contacts->getBy("id", $contact->getId())->getCustomFieldsValues()->getBy("fieldCode", "PHONE"))
                            <i>Телефон - </i>
                            {{
                                $contacts->getBy("id", $contact->getId())
                                ->getCustomFieldsValues()->getBy("fieldCode", "PHONE")->getValues()[0]->value

                                }}
                        @else
                            Телефон не указан
                        @endif

                        @if ($contacts->getBy("id", $contact->getId())->getCustomFieldsValues()
                            && $contacts->getBy("id", $contact->getId())->getCustomFieldsValues()->getBy("fieldCode", "EMAIL"))

                            <i>Почта - </i>
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
        @empty
        Нет заявок
    @endforelse
@endif