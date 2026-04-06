"use client";

import { forwardRef, useMemo, useState, type ReactNode } from 'react';
import { Check, ChevronDown, Globe } from 'lucide-react';

import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from './Command';
import { Popover, PopoverContent, PopoverTrigger } from './Popover';
import { cn } from './utils';

export type CountryDropdownOption = {
  value: string;
  label: string;
  flagCode?: string;
  secondaryLabel?: string;
  keywords?: string[];
  emoji?: string;
  group?: string;
};

type CountryDropdownProps = {
  id?: string;
  options: CountryDropdownOption[];
  value?: string;
  onChange?: (value: string, option: CountryDropdownOption) => void;
  placeholder?: string;
  searchPlaceholder?: string;
  emptyMessage?: string;
  disabled?: boolean;
  className?: string;
  contentClassName?: string;
  placeholderIcon?: ReactNode;
  renderSelectedContent?: (option: CountryDropdownOption) => ReactNode;
  renderOptionContent?: (option: CountryDropdownOption, isSelected: boolean) => ReactNode;
};

function CountryFlag({
  flagCode,
  emoji,
  className,
}: {
  flagCode?: string;
  emoji?: string;
  className?: string;
}) {
  if (flagCode) {
    return (
      <span
        className={cn(`fi fi-${flagCode.toLowerCase()} rounded-[2px] shadow-sm`, className)}
        aria-hidden="true"
      />
    );
  }

  if (emoji) {
    return (
      <span className={cn('inline-flex items-center justify-center text-sm leading-none', className)} aria-hidden="true">
        {emoji}
      </span>
    );
  }

  return <Globe className={cn('h-4 w-4 text-sky-400', className)} aria-hidden="true" />;
}

const defaultRenderSelectedContent = (option: CountryDropdownOption) => (
  <span className="flex min-w-0 items-center gap-2.5 truncate">
    <CountryFlag flagCode={option.flagCode} emoji={option.emoji} className="h-4 w-[22px]" />
    <span className="truncate text-base font-medium">{option.label}</span>
    {option.secondaryLabel ? (
      <span className="shrink-0 text-sm text-slate-500">{option.secondaryLabel}</span>
    ) : null}
  </span>
);

const defaultRenderOptionContent = (option: CountryDropdownOption) => (
  <span className="flex min-w-0 items-center gap-2.5">
    <CountryFlag flagCode={option.flagCode} emoji={option.emoji} className="h-4 w-[22px]" />
    <span className="truncate">{option.label}</span>
    {option.secondaryLabel ? (
      <span className="shrink-0 text-slate-500">{option.secondaryLabel}</span>
    ) : null}
  </span>
);

function buildSearchValue(option: CountryDropdownOption) {
  return [
    option.label,
    option.secondaryLabel,
    option.flagCode,
    option.emoji,
    ...(option.keywords ?? []),
  ]
    .filter(Boolean)
    .join(' ');
}

const CountryDropdown = forwardRef<HTMLButtonElement, CountryDropdownProps>(function CountryDropdown(
  {
    id,
    options,
    value,
    onChange,
    placeholder = 'Select an option',
    searchPlaceholder = 'Search...',
    emptyMessage = 'No option found.',
    disabled = false,
    className,
    contentClassName,
    placeholderIcon,
    renderSelectedContent = defaultRenderSelectedContent,
    renderOptionContent = defaultRenderOptionContent,
  },
  ref,
) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const selectedOption = options.find((option) => option.value === value);
  const filteredOptions = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    if (normalizedSearch === '') {
      return options;
    }

    return options.filter((option) => buildSearchValue(option).toLowerCase().includes(normalizedSearch));
  }, [options, search]);
  const groupedOptions = useMemo(() => {
    const groups: Array<{ key: string; options: CountryDropdownOption[] }> = [];

    filteredOptions.forEach((option) => {
      const groupKey = option.group ?? '__default';
      const existingGroup = groups.find((group) => group.key === groupKey);

      if (existingGroup) {
        existingGroup.options.push(option);
        return;
      }

      groups.push({
        key: groupKey,
        options: [option],
      });
    });

    return groups;
  }, [filteredOptions]);

  return (
    <Popover
      open={open}
      onOpenChange={(nextOpen) => {
        setOpen(nextOpen);

        if (!nextOpen) {
          setSearch('');
        }
      }}
    >
      <PopoverTrigger asChild>
        <button
          ref={ref}
          id={id}
          type="button"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn(
            'flex w-full items-center justify-between gap-2 rounded-xl border-2 bg-white px-4 text-left shadow-sm transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60',
            className,
          )}
        >
          {selectedOption ? (
            renderSelectedContent(selectedOption)
          ) : (
            <span className="flex min-w-0 items-center gap-2.5 truncate text-slate-400">
              {placeholderIcon ?? <Globe className="h-4 w-4 text-sky-400" aria-hidden="true" />}
              <span className="truncate">{placeholder}</span>
            </span>
          )}
          <ChevronDown className="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
        </button>
      </PopoverTrigger>
      <PopoverContent
        align="start"
        className={cn(
          'w-[var(--radix-popover-trigger-width)] min-w-[280px] rounded-xl border-sky-100 p-0 shadow-[0_20px_50px_rgba(14,116,144,0.16)]',
          contentClassName,
        )}
      >
        <Command shouldFilter={false} className="w-full bg-white">
          <CommandInput placeholder={searchPlaceholder} value={search} onValueChange={setSearch} />
          <CommandList className="max-h-[280px]">
            {filteredOptions.length === 0 ? (
              <CommandEmpty>{emptyMessage}</CommandEmpty>
            ) : (
              groupedOptions.map((group, groupIndex) => (
                <div key={group.key}>
                  {groupIndex > 0 ? <CommandSeparator className="my-1 bg-sky-100" /> : null}
                  <CommandGroup>
                    {group.options.map((option) => {
                      const isSelected = option.value === value;

                      return (
                        <CommandItem
                          key={option.value}
                          value={buildSearchValue(option)}
                          onSelect={() => {
                            onChange?.(option.value, option);
                            setSearch('');
                            setOpen(false);
                          }}
                        >
                          <span className="flex min-w-0 flex-1 items-center gap-2.5">
                            {renderOptionContent(option, isSelected)}
                          </span>
                          <Check
                            className={cn(
                              'ml-auto h-4 w-4 shrink-0 text-sky-600 transition-opacity',
                              isSelected ? 'opacity-100' : 'opacity-0',
                            )}
                            aria-hidden="true"
                          />
                        </CommandItem>
                      );
                    })}
                  </CommandGroup>
                </div>
              ))
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
});

export { CountryDropdown, CountryFlag };
